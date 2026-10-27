<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\PaymentAllocation;
use App\Models\Payout;
use App\Models\User;
use App\Services\SequenceService;
use App\Support\Money;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Relevés de gérance et reversements propriétaires (ACC-01 à ACC-03, RG-06).
 * La commission porte sur les loyers encaissés, jamais sur les loyers appelés.
 */
final class StatementService
{
    private const OWNER_LINE_TYPES = ['rent', 'charges', 'penalty'];

    public function __construct(private readonly SequenceService $sequences) {}

    /** @return array<string, mixed> */
    public function compute(Owner $owner, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $mandate = $owner->mandate;
        $rate = (float) ($mandate->commission_rate ?? 0);
        $vatRate = (float) $owner->agency->vat_rate;
        $baseTypes = ($mandate->commission_base ?? 'rent') === 'rent_charges' ? ['rent', 'charges'] : ['rent'];

        $allocations = PaymentAllocation::query()
            ->whereBetween('allocated_at', [$start, $end->copy()->endOfDay()])
            ->whereHas('line', fn ($q) => $q->whereIn('type', self::OWNER_LINE_TYPES))
            ->whereHas('line.invoice.lease.unit.property', fn ($q) => $q->where('owner_id', $owner->id))
            ->with('line.invoice.lease.unit', 'line.invoice.tenant')
            ->get();

        $units = [];
        $collected = 0;
        $commissionBase = 0;
        foreach ($allocations as $allocation) {
            $line = $allocation->line;
            $unit = $line->invoice->lease->unit;
            $key = $unit->id;
            $units[$key] ??= [
                'unit' => $unit->reference.' — '.$unit->label,
                'tenant' => $line->invoice->tenant->displayName(),
                'rent' => 0, 'charges' => 0, 'penalty' => 0,
            ];
            $units[$key][$line->type] += $allocation->amount;
            $collected += $allocation->amount;
            if (in_array($line->type, $baseTypes, true)) {
                $commissionBase += $allocation->amount;
            }
        }

        $expenses = Expense::query()->where('owner_id', $owner->id)->where('charged_to', 'owner')
            ->where('status', 'approved')->whereBetween('spent_on', [$start->toDateString(), $end->toDateString()])
            ->orderBy('spent_on')->get();

        $commission = Money::percent($commissionBase, $rate);
        $commissionVat = ($mandate?->commission_vat ?? true) ? Money::percent($commission, $vatRate) : 0;
        $expensesTotal = (int) $expenses->sum('amount');
        $opening = (int) (OwnerStatement::query()->where('owner_id', $owner->id)
            ->where('period_start', '<', $start->toDateString())->orderByDesc('period_start')
            ->value('closing_balance_amount') ?? 0);

        $available = $opening + $collected - $commission - $commissionVat - $expensesTotal;
        $reserve = min((int) ($mandate->reserve_amount ?? 0), max(0, $available));
        $payout = max(0, $available - $reserve);

        return [
            'period_start' => $start, 'period_end' => $end,
            'opening' => $opening, 'collected' => $collected, 'commission_base' => $commissionBase,
            'commission_rate' => $rate, 'commission' => $commission, 'commission_vat' => $commissionVat,
            'expenses_total' => $expensesTotal, 'reserve' => $reserve, 'payout' => $payout,
            'closing' => $available - $payout,
            'units' => array_values($units),
            'expenses' => $expenses->map(fn (Expense $e): array => [
                'date' => $e->spent_on->format('d/m/Y'), 'label' => $e->label,
                'category' => $e->categoryLabel(), 'amount' => $e->amount,
            ])->all(),
        ];
    }

    public function generate(Owner $owner, Carbon $month, User $by): OwnerStatement
    {
        $data = $this->compute($owner, $month);
        $existing = OwnerStatement::query()->where('owner_id', $owner->id)
            ->whereDate('period_start', $data['period_start'])->first();

        if ($existing !== null && $existing->status !== 'draft') {
            throw new DomainException('Le relevé de cette période est déjà validé.');
        }

        $attributes = [
            'period_end' => $data['period_end']->toDateString(),
            'opening_balance_amount' => $data['opening'],
            'collected_amount' => $data['collected'],
            'commission_amount' => $data['commission'],
            'commission_vat_amount' => $data['commission_vat'],
            'expenses_amount' => $data['expenses_total'],
            'reserve_amount' => $data['reserve'],
            'payout_amount' => $data['payout'],
            'closing_balance_amount' => $data['closing'],
            'details' => ['units' => $data['units'], 'expenses' => $data['expenses'], 'commission_rate' => $data['commission_rate']],
            'created_by' => $by->id,
        ];

        if ($existing !== null) {
            $existing->update($attributes);

            return $existing->refresh();
        }

        return OwnerStatement::create($attributes + [
            'agency_id' => $owner->agency_id,
            'owner_id' => $owner->id,
            'number' => $this->sequences->next($owner->agency_id, 'statement', (int) $month->format('Y')),
            'period_start' => $data['period_start']->toDateString(),
            'status' => 'draft',
        ]);
    }

    /** Séparation des tâches : au-delà du seuil, le valideur n'est pas l'auteur du relevé. */
    public function validate(OwnerStatement $statement, User $by): OwnerStatement
    {
        if ($statement->status !== 'draft') {
            throw new DomainException('Ce relevé est déjà validé.');
        }

        $threshold = (int) $statement->agency->setting('payout_dual_control_amount', config('immora.security.payout_dual_control_amount'));
        if ($statement->payout_amount > $threshold && $statement->created_by === $by->id) {
            throw new DomainException('Au-delà de '.Money::format($threshold).', le relevé doit être validé par une autre personne que celle qui l\'a généré.');
        }

        $statement->update(['status' => 'validated', 'validated_by' => $by->id, 'validated_at' => now()]);
        AuditLog::record('statement.validated', $statement, ['payout' => $statement->payout_amount]);

        return $statement->refresh();
    }

    public function recordPayout(OwnerStatement $statement, string $method, ?string $reference): Payout
    {
        if ($statement->status !== 'validated') {
            throw new DomainException('Le relevé doit être validé avant le reversement.');
        }

        return DB::transaction(function () use ($statement, $method, $reference): Payout {
            $payout = Payout::create([
                'agency_id' => $statement->agency_id,
                'owner_id' => $statement->owner_id,
                'owner_statement_id' => $statement->id,
                'amount' => $statement->payout_amount,
                'method' => $method,
                'reference_note' => $reference,
                'paid_at' => now(),
                'created_by' => auth()->id(),
            ]);
            $statement->update(['status' => 'paid']);
            AuditLog::record('payout.recorded', $payout, ['amount' => $payout->amount]);

            return $payout;
        });
    }
}
