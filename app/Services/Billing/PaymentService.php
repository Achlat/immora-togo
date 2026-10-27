<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Tenant;
use App\Models\TenantCredit;
use App\Services\SequenceService;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Encaissements, imputation (RG-04), avoirs (RENT-09), quittances (RENT-03) et contre-écritures (RENT-06).
 */
final class PaymentService
{
    private const FIRST_TYPES = ['penalty', 'agency_fee'];

    public function __construct(private readonly SequenceService $sequences) {}

    /**
     * @param array{provider?:?string, external_ref?:?string, reference_note?:?string, paid_at?:mixed,
     *              cash_session_id?:?int, created_by?:?int} $options
     */
    public function record(Tenant $tenant, int $amount, string $method, array $options = []): Payment
    {
        if ($amount <= 0) {
            throw new DomainException('Le montant doit être supérieur à zéro.');
        }

        return DB::transaction(function () use ($tenant, $amount, $method, $options): Payment {
            $payment = Payment::create([
                'agency_id' => $tenant->agency_id,
                'tenant_id' => $tenant->id,
                'number' => $this->sequences->next($tenant->agency_id, 'payment'),
                'amount' => $amount,
                'method' => $method,
                'provider' => $options['provider'] ?? null,
                'external_ref' => $options['external_ref'] ?? null,
                'reference_note' => $options['reference_note'] ?? null,
                'cash_session_id' => $options['cash_session_id'] ?? null,
                'paid_at' => $options['paid_at'] ?? now(),
                'status' => 'confirmed',
                'verify_code' => $this->verifyCode(),
                'created_by' => $options['created_by'] ?? auth()->id(),
            ]);

            $remaining = $this->allocate($amount, $this->openLines($tenant, false), $payment, null);

            if ($remaining > 0) {
                TenantCredit::create([
                    'agency_id' => $tenant->agency_id, 'tenant_id' => $tenant->id,
                    'source_payment_id' => $payment->id, 'source' => 'overpayment',
                    'amount' => $remaining, 'remaining_amount' => $remaining,
                ]);
            }

            $this->applyCredits($tenant);
            AuditLog::record('payment.recorded', $payment, ['amount' => $amount, 'method' => $method]);

            return $payment->refresh();
        });
    }

    /** Applique avances et trop-perçus aux échéances mensuelles ouvertes. */
    public function applyCredits(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant): void {
            $credits = TenantCredit::query()->where('tenant_id', $tenant->id)
                ->where('remaining_amount', '>', 0)->orderBy('id')->lockForUpdate()->get();

            foreach ($credits as $credit) {
                $lines = $this->openLines($tenant, true);
                if ($lines->isEmpty()) {
                    return;
                }
                $left = $this->allocate($credit->remaining_amount, $lines, null, $credit);
                $credit->update(['remaining_amount' => $left]);
            }
        });
    }

    public function reverse(Payment $payment, string $reason): Payment
    {
        if ($payment->status !== 'confirmed' || $payment->amount <= 0) {
            throw new DomainException('Cet encaissement a déjà été annulé ou ne peut pas l\'être.');
        }

        return DB::transaction(function () use ($payment, $reason): Payment {
            $reversal = Payment::create([
                'agency_id' => $payment->agency_id,
                'tenant_id' => $payment->tenant_id,
                'number' => $this->sequences->next($payment->agency_id, 'payment'),
                'amount' => -$payment->amount,
                'method' => $payment->method,
                'paid_at' => now(),
                'status' => 'reversal',
                'reversal_of_id' => $payment->id,
                'reversal_reason' => $reason,
                'verify_code' => $this->verifyCode(),
                'created_by' => auth()->id(),
            ]);

            $touched = $this->reverseAllocations($payment->allocations()->with('line')->get(), $reversal->id, null);

            $credits = TenantCredit::query()->where('source_payment_id', $payment->id)->get();
            foreach ($credits as $credit) {
                $creditAllocations = PaymentAllocation::query()->where('tenant_credit_id', $credit->id)
                    ->where('amount', '>', 0)->with('line')->get();
                $touched = $touched->merge($this->reverseAllocations($creditAllocations, null, $credit->id));
                $credit->update(['remaining_amount' => 0]);
            }

            $this->refreshInvoices($touched->unique()->all(), true);
            $payment->update(['status' => 'reversed', 'reversal_reason' => $reason]);
            AuditLog::record('payment.reversed', $payment, ['reason' => $reason, 'reversal' => $reversal->number]);

            return $reversal;
        });
    }

    /**
     * Lignes ouvertes triées selon RG-04 : frais et pénalités les plus anciens, puis échéances
     * de la plus ancienne à la plus récente.
     *
     * @return Collection<int, InvoiceLine>
     */
    private function openLines(Tenant $tenant, bool $rentOnly): Collection
    {
        $penaltiesFirst = ! $tenant->agency?->setting('rent_before_penalties', false);

        return InvoiceLine::query()
            ->whereHas('invoice', function ($query) use ($tenant, $rentOnly): void {
                $query->where('tenant_id', $tenant->id)->whereNotIn('status', ['paid', 'cancelled']);
                if ($rentOnly) {
                    $query->where('kind', 'rent');
                }
            })
            ->whereColumn('paid_amount', '<', 'amount')
            ->with('invoice')
            ->lockForUpdate()
            ->get()
            ->sortBy(fn (InvoiceLine $line): array => [
                $penaltiesFirst && in_array($line->type, self::FIRST_TYPES, true) ? 0 : 1,
                $line->invoice->due_date->format('Ymd'),
                $line->priority,
                $line->id,
            ])
            ->values();
    }

    /** @param Collection<int, InvoiceLine> $lines */
    private function allocate(int $amount, Collection $lines, ?Payment $payment, ?TenantCredit $credit): int
    {
        $remaining = $amount;
        $invoiceIds = [];

        foreach ($lines as $line) {
            if ($remaining <= 0) {
                break;
            }
            $portion = min($remaining, $line->balance());
            if ($portion <= 0) {
                continue;
            }

            PaymentAllocation::create([
                'agency_id' => $line->invoice->agency_id,
                'payment_id' => $payment?->id,
                'tenant_credit_id' => $credit?->id,
                'invoice_line_id' => $line->id,
                'amount' => $portion,
                // Une avance imputée compte pour le mois qu'elle couvre (relevé propriétaire juste).
                'allocated_at' => $payment?->paid_at ?? $line->invoice->period_start,
            ]);
            $line->increment('paid_amount', $portion);
            $remaining -= $portion;
            $invoiceIds[] = $line->invoice_id;

            if ($line->type === 'advance' && $payment !== null) {
                TenantCredit::create([
                    'agency_id' => $line->invoice->agency_id, 'tenant_id' => $line->invoice->tenant_id,
                    'lease_id' => $line->invoice->lease_id, 'source_payment_id' => $payment->id,
                    'source' => 'advance', 'amount' => $portion, 'remaining_amount' => $portion,
                ]);
            }
        }

        $this->refreshInvoices(array_unique($invoiceIds), false);

        return $remaining;
    }

    /**
     * @param Collection<int, PaymentAllocation> $allocations
     * @return Collection<int, int> identifiants des échéances touchées
     */
    private function reverseAllocations(Collection $allocations, ?int $reversalId, ?int $creditId): Collection
    {
        $invoiceIds = collect();
        foreach ($allocations as $allocation) {
            PaymentAllocation::create([
                'agency_id' => $allocation->agency_id,
                'payment_id' => $reversalId,
                'tenant_credit_id' => $creditId,
                'invoice_line_id' => $allocation->invoice_line_id,
                'amount' => -$allocation->amount,
                'allocated_at' => now(),
            ]);
            $allocation->line->decrement('paid_amount', $allocation->amount);
            $invoiceIds->push($allocation->line->invoice_id);
        }

        return $invoiceIds;
    }

    /** @param array<int, int> $invoiceIds */
    private function refreshInvoices(array $invoiceIds, bool $afterReversal): void
    {
        foreach (Invoice::query()->whereIn('id', $invoiceIds)->get() as $invoice) {
            $wasPaid = $invoice->status === 'paid';
            $invoice->refreshTotals();

            if ($invoice->status === 'paid' && $invoice->receipt_number === null) {
                $invoice->update([
                    'receipt_number' => $this->sequences->next($invoice->agency_id, 'receipt'),
                    'verify_code' => $this->verifyCode(),
                ]);
            }

            if ($afterReversal && $wasPaid && $invoice->status !== 'paid') {
                AuditLog::record('receipt.cancelled', $invoice, ['receipt' => $invoice->receipt_number]);
                $invoice->update(['receipt_number' => null, 'verify_code' => null]);
            }
        }
    }

    private function verifyCode(): string
    {
        return Str::upper(Str::random(10));
    }
}
