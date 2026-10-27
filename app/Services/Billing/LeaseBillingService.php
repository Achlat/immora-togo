<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Lease;
use App\Services\SequenceService;
use App\Support\Money;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Activation des baux, facture d'entrée et échéancier mensuel (LEASE-02, LEASE-03, RG-02, RG-03).
 */
final class LeaseBillingService
{
    public function __construct(
        private readonly SequenceService $sequences,
        private readonly PaymentService $payments,
    ) {}

    public function activate(Lease $lease): Lease
    {
        if ($lease->status !== 'draft') {
            throw new DomainException('Seul un bail en brouillon peut être activé.');
        }

        $this->assertNoOverlap($lease);

        return DB::transaction(function () use ($lease): Lease {
            $lease->update(['status' => 'active', 'activated_at' => now()]);
            $this->createEntryInvoice($lease);

            $month = $lease->start_date->copy()->startOfMonth()->addMonth();
            $lastMonth = now()->startOfMonth();
            while ($month->lte($lastMonth)) {
                $this->createMonthlyInvoice($lease, $month);
                $month->addMonth();
            }

            $lease->unit->refreshStatus();
            AuditLog::record('lease.activated', $lease);

            return $lease->refresh();
        });
    }

    /** RG-09 : deux baux actifs ne se chevauchent jamais sur le même lot. */
    private function assertNoOverlap(Lease $lease): void
    {
        $overlap = Lease::query()
            ->where('unit_id', $lease->unit_id)
            ->whereKeyNot($lease->id)
            ->whereIn('status', ['active', 'notice'])
            ->where(function ($query) use ($lease): void {
                $query->whereNull('end_date')->orWhere('end_date', '>=', $lease->start_date);
            })
            ->exists();

        if ($overlap) {
            throw new DomainException('Ce lot a déjà un bail actif sur cette période.');
        }
    }

    /** RG-03 : prorata du premier mois, caution, avance et frais d'agence. */
    public function createEntryInvoice(Lease $lease): Invoice
    {
        $start = $lease->start_date->copy();
        $daysInMonth = $start->daysInMonth;
        $occupiedDays = $daysInMonth - $start->day + 1;
        $isFullMonth = $start->day === 1;

        $rent = $isFullMonth ? $lease->rent_amount : Money::prorata($lease->rent_amount, $occupiedDays, $daysInMonth);
        $charges = $isFullMonth ? $lease->charges_amount : Money::prorata($lease->charges_amount, $occupiedDays, $daysInMonth);
        $monthLabel = $start->translatedFormat('F Y');
        $rentLabel = $isFullMonth ? "Loyer {$monthLabel}" : "Loyer {$monthLabel} au prorata ({$occupiedDays} j / {$daysInMonth})";

        $lines = [
            ['type' => 'rent', 'label' => $rentLabel, 'amount' => $rent],
            ['type' => 'charges', 'label' => "Charges {$monthLabel}", 'amount' => $charges],
            ['type' => 'vat', 'label' => 'TVA '.rtrim(rtrim((string) $lease->vat_rate, '0'), '.').' %', 'amount' => Money::percent($rent + $charges, $lease->vat_rate)],
            ['type' => 'deposit', 'label' => 'Caution (dépôt de garantie)', 'amount' => $lease->deposit_amount],
            ['type' => 'advance', 'label' => 'Avance sur loyers', 'amount' => $lease->advance_amount],
            ['type' => 'agency_fee', 'label' => "Frais d'agence", 'amount' => $lease->agency_fee_amount],
        ];

        return $this->createInvoice($lease, 'entry', $start, $start->copy()->endOfMonth(), $start, $lines);
    }

    public function createMonthlyInvoice(Lease $lease, Carbon $month): ?Invoice
    {
        $periodStart = $month->copy()->startOfMonth();
        if ($periodStart->lte($lease->start_date) || ($lease->end_date !== null && $periodStart->gt($lease->end_date))) {
            return null;
        }

        $exists = Invoice::query()->where('lease_id', $lease->id)->where('kind', 'rent')
            ->whereDate('period_start', $periodStart)->exists();
        if ($exists) {
            return null;
        }

        $dueDate = $periodStart->copy()->day(min($lease->due_day, $periodStart->daysInMonth));
        $monthLabel = $periodStart->translatedFormat('F Y');
        $lines = [
            ['type' => 'rent', 'label' => "Loyer {$monthLabel}", 'amount' => $lease->rent_amount],
            ['type' => 'charges', 'label' => "Charges {$monthLabel}", 'amount' => $lease->charges_amount],
            ['type' => 'vat', 'label' => 'TVA', 'amount' => Money::percent($lease->rent_amount + $lease->charges_amount, $lease->vat_rate)],
        ];

        $invoice = $this->createInvoice($lease, 'rent', $periodStart, $periodStart->copy()->endOfMonth(), $dueDate, $lines);
        $this->payments->applyCredits($lease->tenant);

        return $invoice->refresh();
    }

    /** Génère l'échéance du mois donné pour tous les baux actifs de l'agence active. */
    public function generateForMonth(Carbon $month): int
    {
        $count = 0;
        Lease::query()->whereIn('status', ['active', 'notice'])->with('tenant')->each(
            function (Lease $lease) use ($month, &$count): void {
                if ($this->createMonthlyInvoice($lease, $month) !== null) {
                    $count++;
                }
            }
        );

        return $count;
    }

    public function terminate(Lease $lease, Carbon $endDate, ?string $reason = null): Lease
    {
        return DB::transaction(function () use ($lease, $endDate, $reason): Lease {
            $lease->update(['status' => 'terminated', 'end_date' => $endDate, 'terminated_at' => $endDate]);

            $lease->invoices()->where('kind', 'rent')->where('period_start', '>', $endDate)
                ->where('paid_amount', 0)->update(['status' => 'cancelled']);

            $lease->unit->refreshStatus();
            AuditLog::record('lease.terminated', $lease, ['end_date' => $endDate->toDateString(), 'reason' => $reason]);

            return $lease->refresh();
        });
    }

    /** @param list<array{type:string,label:string,amount:int}> $lines */
    private function createInvoice(Lease $lease, string $kind, Carbon $start, Carbon $end, Carbon $due, array $lines): Invoice
    {
        return DB::transaction(function () use ($lease, $kind, $start, $end, $due, $lines): Invoice {
            $invoice = Invoice::create([
                'agency_id' => $lease->agency_id,
                'lease_id' => $lease->id,
                'tenant_id' => $lease->tenant_id,
                'number' => $this->sequences->next($lease->agency_id, 'invoice', (int) $start->format('Y')),
                'kind' => $kind,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'due_date' => $due->toDateString(),
            ]);

            foreach ($lines as $line) {
                if ($line['amount'] <= 0) {
                    continue;
                }
                $invoice->lines()->create($line + ['priority' => InvoiceLine::priorityFor($line['type'])]);
            }

            $invoice->refreshTotals();

            return $invoice;
        });
    }
}
