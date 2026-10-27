<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\AuthEvent;
use App\Models\User;
use App\Support\Qr;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Double authentification TOTP (RFC 6238) et codes de secours (AUTH-03).
 */
final class TwoFactorService
{
    public function __construct(private readonly Google2FA $google2fa) {}

    public function newSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function qrSvg(User $user, string $secret): string
    {
        $label = $user->email ?? $user->phone_e164 ?? $user->fullName();
        $url = $this->google2fa->getQRCodeUrl('Immora', $label, $secret);

        return Qr::svgDataUri($url);
    }

    public function verifySecret(string $secret, string $code): bool
    {
        return (bool) $this->google2fa->verifyKey($secret, preg_replace('/\D/', '', $code), 1);
    }

    /** @return list<string> */
    public function enable(User $user, string $secret): array
    {
        $codes = $this->newRecoveryCodes();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at' => now(),
        ])->save();
        AuthEvent::log('two_factor_enabled', $user->id);

        return $codes;
    }

    public function verify(User $user, string $code): bool
    {
        $clean = trim($code);
        if (preg_match('/^\d{6}$/', preg_replace('/\s/', '', $clean)) === 1) {
            return $this->verifySecret((string) $user->two_factor_secret, $clean);
        }

        return $this->useRecoveryCode($user, $clean);
    }

    /** @return list<string> */
    public function newRecoveryCodes(): array
    {
        return collect(range(1, 10))
            ->map(fn () => Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)))
            ->all();
    }

    private function useRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        $normalized = Str::upper($code);
        if (! in_array($normalized, $codes, true)) {
            return false;
        }

        $user->forceFill(['two_factor_recovery_codes' => array_values(array_diff($codes, [$normalized]))])->save();

        return true;
    }
}
