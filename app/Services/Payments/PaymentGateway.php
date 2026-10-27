<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\PaymentIntent;

/**
 * Contrat d'un agrégateur de paiement (CinetPay, PayDunya, FedaPay…). Le code métier
 * ne dépend que de cette interface (CDC chap. 24).
 */
interface PaymentGateway
{
    public function name(): string;

    /** Retourne l'URL de la page de paiement où rediriger le payeur. */
    public function initiate(PaymentIntent $intent, string $returnUrl, string $notifyUrl): string;

    /**
     * Interroge l'agrégateur pour connaître l'état réel du paiement.
     *
     * @return array{status: 'accepted'|'refused'|'pending', external_ref: ?string, operator: ?string}
     */
    public function fetchStatus(PaymentIntent $intent): array;

    /** Vérifie la signature d'une notification entrante (webhook). */
    public function verifyNotification(array $payload, ?string $signature): bool;
}
