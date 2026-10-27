<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\PaymentIntent;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Intégration CinetPay (Orange Money, MTN, Moov, Wave, carte) — API checkout v2.
 * Le statut est toujours re-vérifié par appel API avant tout enregistrement (PAY-02).
 */
final class CinetPayGateway implements PaymentGateway
{
    private const OPERATORS = ['OM' => 'orange', 'OMCI' => 'orange', 'MOMO' => 'mtn', 'FLOOZ' => 'moov', 'WAVECI' => 'wave', 'WAVE' => 'wave'];

    /** Champs signés de la notification, dans l'ordre imposé par CinetPay. */
    private const SIGNED_FIELDS = [
        'cpm_site_id', 'cpm_trans_id', 'cpm_trans_date', 'cpm_amount', 'cpm_currency', 'signature',
        'payment_method', 'cel_phone_num', 'cpm_phone_prefixe', 'cpm_language', 'cpm_version',
        'cpm_payment_config', 'cpm_page_action', 'cpm_custom', 'cpm_designation', 'cpm_error_message',
    ];

    public function __construct(private readonly array $config) {}

    public function name(): string
    {
        return 'cinetpay';
    }

    public function initiate(PaymentIntent $intent, string $returnUrl, string $notifyUrl): string
    {
        $tenant = $intent->tenant;
        $response = Http::timeout(20)->post($this->config['base_url'].'/payment', [
            'apikey' => $this->config['api_key'],
            'site_id' => $this->config['site_id'],
            'transaction_id' => $intent->reference,
            'amount' => $intent->amount + $intent->fee_amount,
            'currency' => $intent->agency->currency,
            'description' => 'Loyer '.$intent->agency->name,
            'notify_url' => $notifyUrl,
            'return_url' => $returnUrl,
            'channels' => 'ALL',
            'customer_name' => $tenant->last_name ?? $tenant->company_name,
            'customer_surname' => $tenant->first_name ?? '',
            'customer_phone_number' => $tenant->phone_e164,
        ]);

        $url = $response->json('data.payment_url');
        if (! $response->successful() || ! is_string($url)) {
            throw new RuntimeException('Le service de paiement est momentanément indisponible. Réessayez dans quelques minutes.');
        }

        return $url;
    }

    public function fetchStatus(PaymentIntent $intent): array
    {
        $response = Http::timeout(20)->post($this->config['base_url'].'/payment/check', [
            'apikey' => $this->config['api_key'],
            'site_id' => $this->config['site_id'],
            'transaction_id' => $intent->reference,
        ]);

        $status = strtoupper((string) $response->json('data.status'));

        return [
            'status' => match ($status) {
                'ACCEPTED' => 'accepted',
                'REFUSED', 'CANCELED', 'CANCELLED' => 'refused',
                default => 'pending',
            },
            'external_ref' => $response->json('data.operator_id') ?? $intent->reference,
            'operator' => self::OPERATORS[strtoupper((string) $response->json('data.payment_method'))] ?? 'mobile_money',
        ];
    }

    public function verifyNotification(array $payload, ?string $signature): bool
    {
        $secret = $this->config['secret_key'] ?? null;
        if (empty($secret)) {
            return true;
        }

        $data = implode('', array_map(fn (string $field): string => (string) ($payload[$field] ?? ''), self::SIGNED_FIELDS));

        return $signature !== null && hash_equals(hash_hmac('sha256', $data, $secret), $signature);
    }
}
