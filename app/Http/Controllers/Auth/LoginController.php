<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthEvent;
use App\Models\User;
use App\Services\Auth\LoginGuard;
use App\Services\Auth\SessionStarter;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Connexion par e-mail ou téléphone + mot de passe (AUTH-01, AUTH-05 à AUTH-07).
 */
final class LoginController extends Controller
{
    public function __construct(
        private readonly LoginGuard $guard,
        private readonly SessionStarter $starter,
    ) {}

    public function show(Request $request): View
    {
        $identifier = (string) $request->old('identifier', '');

        return view('auth.login', [
            'captcha' => $identifier !== '' && $this->guard->needsCaptcha($identifier),
            'tab' => $request->query('mode') === 'sms' ? 'sms' : 'password',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string', 'max:128'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'identifier.required' => 'Saisissez votre e-mail ou votre numéro de téléphone.',
            'password.required' => 'Saisissez votre mot de passe.',
        ]);

        $identifier = trim($data['identifier']);
        $this->guard->throttle($identifier, $request);

        if ($this->guard->needsCaptcha($identifier) && ! $this->guard->verifyCaptcha($request->input('cf-turnstile-response'), $request)) {
            throw ValidationException::withMessages(['identifier' => 'Merci de confirmer que vous n\'êtes pas un robot.']);
        }

        $user = $this->findUser($identifier);
        if ($user === null) {
            // Même coût de calcul qu'un vrai compte : on ne révèle pas si l'identifiant existe.
            Hash::check($data['password'], Cache::rememberForever('immora.timing-hash', fn () => Hash::make(Str::random(40))));
            $this->guard->fail(null, $identifier, $request, 'unknown_identifier');
        }

        $this->guard->assertNotLocked($user);

        if ($user->password === null || ! Hash::check($data['password'], $user->password)) {
            $this->guard->fail($user, $identifier, $request, 'bad_password');
        }

        if (Hash::needsRehash($user->password)) {
            $user->forceFill(['password' => $data['password']])->save();
        }

        return $this->starter->begin($user, $identifier, $request, (bool) ($data['remember'] ?? false), 'password');
    }

    public function destroy(Request $request): RedirectResponse
    {
        AuthEvent::log('logout', $request->user()?->id);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Vous êtes déconnecté. À bientôt.');
    }

    private function findUser(string $identifier): ?User
    {
        if (Phone::looksLikePhone($identifier)) {
            $phone = Phone::normalize($identifier);

            return $phone === null ? null : User::query()->where('phone_e164', $phone)->first();
        }

        return User::query()->where('email', mb_strtolower($identifier))->first();
    }
}
