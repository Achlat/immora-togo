<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthEvent;
use App\Models\User;
use App\Services\Auth\DeviceService;
use App\Services\Auth\SessionStarter;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Double authentification : défi à la connexion et activation (AUTH-03).
 */
final class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactor,
        private readonly DeviceService $devices,
        private readonly SessionStarter $starter,
    ) {}

    public function challenge(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.pending')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'trust' => ['nullable', 'boolean'],
        ], ['code.required' => 'Saisissez le code à 6 chiffres ou un code de secours.']);

        $pending = $request->session()->get('login.pending');
        $user = $pending ? User::query()->find($pending['user_id']) : null;
        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $this->twoFactor->verify($user, $data['code'])) {
            AuthEvent::log('two_factor_failed', $user->id);

            return back()->withErrors(['code' => 'Code incorrect. Vérifiez l\'heure de votre téléphone ou utilisez un code de secours.']);
        }

        AuthEvent::log('two_factor_ok', $user->id);
        if ($data['trust'] ?? false) {
            $this->devices->trustForTwoFactor($user, $request);
        }

        return $this->starter->complete($user, $pending['identifier'], $request, (bool) $pending['remember'], $pending['method']);
    }

    public function setup(Request $request): View
    {
        $user = $request->user();
        if ($user->hasTwoFactor()) {
            return view('auth.two-factor-setup', ['enabled' => true]);
        }

        $secret = $request->session()->get('two_factor.secret') ?? $this->twoFactor->newSecret();
        $request->session()->put('two_factor.secret', $secret);

        return view('auth.two-factor-setup', [
            'enabled' => false,
            'qr' => $this->twoFactor->qrSvg($user, $secret),
            'secret' => trim(chunk_split($secret, 4, ' ')),
        ]);
    }

    public function enable(Request $request): View|RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'digits:6']], [
            'code.required' => 'Saisissez le code affiché dans votre application.',
            'code.digits' => 'Le code comporte 6 chiffres.',
        ]);

        $secret = $request->session()->get('two_factor.secret');
        if ($secret === null || ! $this->twoFactor->verifySecret($secret, $data['code'])) {
            return back()->withErrors(['code' => 'Code incorrect. Scannez à nouveau le QR code et saisissez le code affiché.']);
        }

        $codes = $this->twoFactor->enable($request->user(), $secret);
        $request->session()->forget('two_factor.secret');
        $this->devices->trustForTwoFactor($request->user(), $request);

        return view('auth.two-factor-setup', ['enabled' => true, 'codes' => $codes]);
    }
}
