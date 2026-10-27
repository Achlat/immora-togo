<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\AuthEvent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Dernière étape de toute connexion réussie : 2FA éventuelle, ouverture de session,
 * appareil, choix de l'agence et redirection selon le rôle.
 */
final class SessionStarter
{
    public function __construct(
        private readonly DeviceService $devices,
        private readonly LoginGuard $guard,
    ) {}

    public function begin(User $user, string $identifier, Request $request, bool $remember, string $method): RedirectResponse
    {
        if ($user->hasTwoFactor() && ! $this->devices->isTrustedForTwoFactor($user, $request)) {
            $request->session()->put('login.pending', [
                'user_id' => $user->id, 'identifier' => $identifier, 'remember' => $remember, 'method' => $method,
            ]);

            return redirect()->route('two-factor.challenge');
        }

        return $this->complete($user, $identifier, $request, $remember, $method);
    }

    public function complete(User $user, string $identifier, Request $request, bool $remember, string $method): RedirectResponse
    {
        $remember = $remember && ! $user->is_super_admin;
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $request->session()->forget('login.pending');
        $request->session()->put('last_activity_at', now()->timestamp);

        $this->guard->succeed($user, $identifier, $request);
        $this->devices->register($user, $request);
        AuthEvent::log($method === 'otp' ? 'otp_login' : 'login', $user->id);

        return $this->redirectAfterLogin($user, $request);
    }

    public function redirectAfterLogin(User $user, Request $request): RedirectResponse
    {
        $agencies = $user->activeAgencies()->get();

        if ($agencies->isEmpty()) {
            return $user->is_super_admin
                ? redirect()->route('admin.agencies.index')
                : $this->reject($request);
        }

        $preferred = $agencies->firstWhere('id', $user->last_agency_id);
        if ($agencies->count() > 1 && $preferred === null) {
            return redirect()->route('agency.select');
        }

        $agency = $preferred ?? $agencies->first();
        $request->session()->put('agency_id', $agency->id);
        $user->forceFill(['last_agency_id' => $agency->id])->save();

        return in_array($agency->pivot->role, ['owner', 'tenant'], true)
            ? redirect()->intended(route('portal.home'))
            : redirect()->intended(route('dashboard'));
    }

    private function reject(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();

        return redirect()->route('login')->withErrors([
            'identifier' => "Ce compte n'est rattaché à aucune agence active. Contactez votre agence.",
        ]);
    }
}
