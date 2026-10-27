<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\AuthEvent;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\Messaging\Notifier;
use DomainException;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Codes à usage unique par SMS (AUTH-02) : 6 chiffres, 5 minutes, 5 essais, 3 envois par heure.
 */
final class OtpService
{
    public function __construct(private readonly Notifier $notifier) {}

    /** @return string le code en clair (retourné uniquement pour le mode démonstration) */
    public function send(User $user, string $purpose): string
    {
        $key = "otp-send:{$user->id}";
        if (RateLimiter::tooManyAttempts($key, config('immora.security.otp_per_hour'))) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            throw new DomainException("Vous avez demandé plusieurs codes. Réessayez dans {$minutes} minutes.");
        }
        RateLimiter::hit($key, 3600);

        OtpCode::query()->where('user_id', $user->id)->where('purpose', $purpose)
            ->whereNull('consumed_at')->update(['consumed_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        OtpCode::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'channel' => 'sms',
            'code_hash' => hash('sha256', $code),
            'expires_at' => now()->addMinutes(config('immora.security.otp_ttl_minutes')),
            'ip_address' => request()?->ip(),
        ]);

        $this->notifier->otp($user, $code);
        AuthEvent::log('otp_sent', $user->id, ['purpose' => $purpose]);

        return $code;
    }

    /**
     * @return array{ok: bool, message: ?string}
     */
    public function verify(User $user, string $purpose, string $code): array
    {
        $otp = OtpCode::query()->where('user_id', $user->id)->where('purpose', $purpose)
            ->whereNull('consumed_at')->latest('id')->first();

        if ($otp === null || $otp->expires_at->isPast()) {
            return ['ok' => false, 'message' => 'Ce code a expiré. Demandez-en un nouveau, il arrive en quelques secondes.'];
        }

        $max = config('immora.security.otp_max_attempts');
        if ($otp->attempts >= $max) {
            return ['ok' => false, 'message' => 'Trop d\'essais pour ce code. Demandez-en un nouveau.'];
        }

        $otp->increment('attempts');
        if (! hash_equals($otp->code_hash, hash('sha256', preg_replace('/\D/', '', $code)))) {
            $left = $max - $otp->attempts;

            return ['ok' => false, 'message' => $left > 0
                ? "Code incorrect. Il vous reste {$left} essai".($left > 1 ? 's' : '').'.'
                : 'Code incorrect. Demandez un nouveau code.'];
        }

        $otp->update(['consumed_at' => now()]);

        return ['ok' => true, 'message' => null];
    }
}
