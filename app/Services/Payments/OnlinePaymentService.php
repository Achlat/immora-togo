<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentIntent;
use App\Models\Tenant;
use App\Services\Billing\PaymentService;
use App\Services\Messaging\Notifier;
use App\Support\CurrentAgency;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Paiement en ligne des loyers (PAY-01 à PAY-03). Traitement idempotent :
 * un même paiement n'est jamais enregistré deux fois.
 */
final class OnlinePaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly PaymentService $payments,
        private readonly Notifier $notifier,
        private readonly CurrentAgency $current,
    ) {}

    public function start(Tenant $tenant, ?Invoice $invoice, int $amount): string
    {
        $agency = $tenant->agency;
        $feePayer = $agency->setting('online_fee_payer', 'agency');
        $fee = $feePayer === 'tenant' ? Money::percent($amount, (float) $agency->setting('online_fee_rate', 2.5)) : 0;

        $intent = PaymentIntent::create([
            'agency_id' => $tenant->agency_id,
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice?->id,
            'reference' => 'IMM'.Str::upper(Str::random(12)),
            'provider' => $this->gateway->name(),
            'amount' => $amount,
            'fee_amount' => $fee,
        ]);

        return $this->gateway->initiate(
            $intent,
            route('payments.return', $intent->reference),
            route('payments.notify', $this->gateway->name()),
        );
    }

    public function confirm(PaymentIntent $intent): ?Payment
    {
        return DB::transaction(function () use ($intent): ?Payment {
            $intent = PaymentIntent::query()->whereKey($intent->id)->lockForUpdate()->firstOrFail();
            if ($intent->status === 'accepted') {
                return Payment::withoutGlobalScopes()->find($intent->payment_id);
            }

            $result = $this->gateway->fetchStatus($intent);
            if ($result['status'] === 'refused') {
                $intent->update(['status' => 'failed']);

                return null;
            }
            if ($result['status'] !== 'accepted') {
                return null;
            }

            $this->current->set($intent->agency, 'system');
            $tenant = Tenant::query()->findOrFail($intent->tenant_id);
            $externalRef = $result['external_ref'] ?? $intent->reference;

            $existing = Payment::query()->where('provider', $result['operator'])->where('external_ref', $externalRef)->first();
            $payment = $existing ?? $this->payments->record($tenant, $intent->amount, 'mobile_money', [
                'provider' => $result['operator'],
                'external_ref' => $externalRef,
                'reference_note' => 'Paiement en ligne '.$intent->reference,
                'created_by' => $tenant->user_id,
            ]);

            $intent->update(['status' => 'accepted', 'external_ref' => $externalRef, 'payment_id' => $payment->id]);

            if ($existing === null) {
                $this->notifier->paymentReceived($payment, $intent->invoice, $intent->agency, route('verify.show', $payment->verify_code));
            }

            return $payment;
        });
    }
}
