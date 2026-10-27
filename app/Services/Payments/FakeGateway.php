<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\PaymentIntent;

/**
 * Agrégateur simulé pour la démonstration et les tests : une page imite l'écran
 * de validation Mobile Money. À remplacer par CinetPay en production.
 */
final class FakeGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function initiate(PaymentIntent $intent, string $returnUrl, string $notifyUrl): string
    {
        return route('payments.simulator', $intent->reference);
    }

    public function fetchStatus(PaymentIntent $intent): array
    {
        $payload = $intent->provider_payload ?? [];

        return [
            'status' => $payload['simulated_status'] ?? 'pending',
            'external_ref' => $payload['simulated_ref'] ?? null,
            'operator' => $payload['simulated_operator'] ?? null,
        ];
    }

    public function verifyNotification(array $payload, ?string $signature): bool
    {
        return true;
    }
}
