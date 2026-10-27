<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\AuthEvent;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Services\Messaging\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Reconnaissance des appareils : alerte de nouvel appareil (AUTH-13)
 * et « faire confiance à cet appareil 30 jours » pour la 2FA.
 */
final class DeviceService
{
    private const COOKIE = 'immora_device';

    public function __construct(private readonly Notifier $notifier) {}

    public function register(User $user, Request $request): void
    {
        $token = $this->token($request);
        $hash = hash('sha256', $token);
        $device = TrustedDevice::query()->where('user_id', $user->id)->where('token_hash', $hash)->first();

        if ($device === null) {
            $isFirstDevice = ! TrustedDevice::query()->where('user_id', $user->id)->exists();
            TrustedDevice::create([
                'user_id' => $user->id, 'token_hash' => $hash,
                'label' => $this->describe($request), 'last_seen_at' => now(),
            ]);
            if (! $isFirstDevice) {
                AuthEvent::log('new_device', $user->id, ['device' => $this->describe($request)]);
                $this->notifier->newDevice($user, $this->describe($request));
            }

            return;
        }

        $device->update(['last_seen_at' => now()]);
    }

    public function isTrustedForTwoFactor(User $user, Request $request): bool
    {
        $cookie = $request->cookie(self::COOKIE);
        if (! is_string($cookie) || $cookie === '') {
            return false;
        }

        return TrustedDevice::query()->where('user_id', $user->id)
            ->where('token_hash', hash('sha256', $cookie))
            ->where('two_factor_trusted_until', '>', now())->exists();
    }

    public function trustForTwoFactor(User $user, Request $request): void
    {
        $hash = hash('sha256', $this->token($request));
        TrustedDevice::query()->updateOrCreate(
            ['user_id' => $user->id, 'token_hash' => $hash],
            ['label' => $this->describe($request), 'last_seen_at' => now(),
                'two_factor_trusted_until' => now()->addDays(config('immora.security.trusted_device_days'))],
        );
    }

    public function describe(Request $request): string
    {
        $agent = (string) $request->userAgent();
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Navigateur',
        };
        $system = match (true) {
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'système inconnu',
        };

        return "{$browser} / {$system}";
    }

    private function token(Request $request): string
    {
        $token = $request->cookie(self::COOKIE);
        if (! is_string($token) || strlen($token) < 40) {
            $token = Str::random(48);
        }
        Cookie::queue(Cookie::make(self::COOKIE, $token, 60 * 24 * 365, null, null, null, true, false, 'lax'));

        return $token;
    }
}
