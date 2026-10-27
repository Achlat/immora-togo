<?php

declare(strict_types=1);

namespace App\Services\Messaging;

/**
 * Contrat d'envoi SMS / WhatsApp. Chaque fournisseur (Orange SMS API, Twilio, WhatsApp Cloud…)
 * se branche via une implémentation de cette interface (CDC chap. 24).
 */
interface SmsSender
{
    public function send(string $phoneE164, string $body, string $template, ?int $agencyId = null): void;
}
