<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Models\MessageLog;
use Illuminate\Support\Facades\Log;

/**
 * Pilote de développement : les messages sont écrits dans le journal au lieu d'être envoyés.
 */
final class LogSmsSender implements SmsSender
{
    public function send(string $phoneE164, string $body, string $template, ?int $agencyId = null): void
    {
        MessageLog::create([
            'agency_id' => $agencyId,
            'channel' => 'sms',
            'recipient' => $phoneE164,
            'template' => $template,
            'body' => $body,
            'status' => 'logged',
        ]);

        Log::info("[SMS {$template}] {$phoneE164} : {$body}");
    }
}
