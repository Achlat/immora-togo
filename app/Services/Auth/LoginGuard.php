<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\AuthEvent;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Services\Messaging\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Protection de la page de connexion (AUTH-05, AUTH-06, AUTH-07) :
 * limitation de débit, verrouillage progressif et défi anti-robot.
 */
final class LoginGuard
{
    private const NEUTRAL_ERROR = 'Identifiant ou mot de passe incorrect. Vérifiez votre saisie ou utilisez la connexion par code SMS.';

    public function __construct(private readonly Notifier $notifier) {}

    public function throttle(string $identifier, Request $request): void
    {
        foreach ([$this->ipKey($request) => 10, $this->identifierKey($identifier) => 5] as $key => $max) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $seconds = RateLimiter::availableIn($key);
                throw ValidationException::withMessages([
                    'identifier' => "Trop de tentatives. Réessayez dans {$seconds} secondes.",
                ]);
            }
        }
    }

    public function needsCaptcha(string $identifier): bool
    {
        return $this->captchaEnabled()
            && RateLimiter::attempts($this->failureKey($identifier)) >= config('immora.security.captcha_after');
    }

    public function captchaEnabled(): bool
    {
        return ! empty(config('immora.turnstile.secret_key'));
    }

    public function verifyCaptcha(?string $token, Request $request): bool
    {
        if (! $this->captchaEnabled()) {
            return true;
        }

        $response = Http::asForm()->timeout(10)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => config('immora.turnstile.secret_key'),
            'response' => (string) $token,
            'remoteip' => $request->ip(),
        ]);

        return (bool) $response->json('success');
    }

    public function assertNotLocked(User $user): void
    {
        if ($user->status === 'disabled') {
            throw ValidationException::withMessages([
                'identifier' => "Ce compte n'est plus actif. Contactez l'administrateur de votre agence.",
            ]);
        }

        if ($user->isLocked()) {
            $message = $user->status === 'locked'
                ? 'Par sécurité, ce compte est verrouillé. Réinitialisez votre mot de passe pour le débloquer.'
                : 'Par sécurité, ce compte est suspendu pendant '.max(1, (int) ceil(now()->diffInSeconds($user->locked_until) / 60))
                    .' minutes. Vous pouvez réinitialiser votre mot de passe dès maintenant.';

            throw ValidationException::withMessages(['identifier' => $message]);
        }
    }

    /** Enregistre un échec et applique le verrouillage progressif. Lève toujours le message neutre. */
    public function fail(?User $user, string $identifier, Request $request, string $reason): never
    {
        RateLimiter::hit($this->ipKey($request), 60);
        RateLimiter::hit($this->identifierKey($identifier), 60);
        RateLimiter::hit($this->failureKey($identifier), 900);

        LoginAttempt::create([
            'identifier' => mb_substr($identifier, 0, 190), 'user_id' => $user?->id,
            'ip_address' => (string) $request->ip(), 'success' => false, 'reason' => $reason,
        ]);

        if ($user !== null) {
            $this->applyLockout($user);
            AuthEvent::log('login_failed', $user->id, ['reason' => $reason]);
        }

        throw ValidationException::withMessages(['identifier' => self::NEUTRAL_ERROR]);
    }

    public function succeed(User $user, string $identifier, Request $request): void
    {
        RateLimiter::clear($this->identifierKey($identifier));
        RateLimiter::clear($this->failureKey($identifier));
        $user->forceFill(['failed_logins' => 0, 'locked_until' => null, 'last_login_at' => now()])->save();

        LoginAttempt::create([
            'identifier' => mb_substr($identifier, 0, 190), 'user_id' => $user->id,
            'ip_address' => (string) $request->ip(), 'success' => true,
        ]);
    }

    private function applyLockout(User $user): void
    {
        $failures = $user->failed_logins + 1;
        $updates = ['failed_logins' => $failures];

        if ($failures >= config('immora.security.hard_lock_after')) {
            $updates['status'] = 'locked';
        } else {
            foreach (array_reverse(config('immora.security.lock_after'), true) as $threshold => $minutes) {
                if ($failures >= $threshold) {
                    $updates['locked_until'] = now()->addMinutes($minutes);
                    break;
                }
            }
        }

        $user->forceFill($updates)->save();

        if (isset($updates['locked_until']) || isset($updates['status'])) {
            AuthEvent::log('locked', $user->id, ['failures' => $failures]);
        }
        if ($failures === 10) {
            $this->alertAdmins($user);
        }
    }

    private function alertAdmins(User $locked): void
    {
        foreach ($locked->agencies as $agency) {
            $agency->users()->wherePivot('role', 'admin')->get()
                ->each(fn (User $admin) => $this->notifier->accountLocked($admin, $locked));
        }
    }

    private function ipKey(Request $request): string
    {
        return 'login-ip:'.$request->ip();
    }

    private function identifierKey(string $identifier): string
    {
        return 'login-id:'.sha1(mb_strtolower($identifier));
    }

    private function failureKey(string $identifier): string
    {
        return 'login-fail:'.sha1(mb_strtolower($identifier));
    }
}
