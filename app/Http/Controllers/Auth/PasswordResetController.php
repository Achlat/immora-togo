<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthEvent;
use App\Models\PasswordReset;
use App\Models\User;
use App\Rules\StrongPassword;
use App\Services\Messaging\Notifier;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Mot de passe oublié (AUTH-04) : jeton de 64 caractères, empreinte SHA-256 en base,
 * 30 minutes, usage unique, message de confirmation toujours identique.
 */
final class PasswordResetController extends Controller
{
    private const NEUTRAL = 'Si un compte correspond, vous allez recevoir un lien dans quelques instants.';

    public function __construct(private readonly Notifier $notifier) {}

    public function request(): View
    {
        return view('auth.forgot');
    }

    public function email(Request $request): RedirectResponse
    {
        $data = $request->validate(['identifier' => ['required', 'string', 'max:190']], [
            'identifier.required' => 'Saisissez votre e-mail ou votre numéro de téléphone.',
        ]);

        $identifier = trim($data['identifier']);
        $user = Phone::looksLikePhone($identifier)
            ? User::query()->where('phone_e164', Phone::normalize($identifier))->first()
            : User::query()->where('email', mb_strtolower($identifier))->first();

        if ($user !== null && $user->status !== 'disabled') {
            $token = Str::random(64);
            PasswordReset::create([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addMinutes(config('immora.security.reset_ttl_minutes')),
            ]);
            $url = route('password.reset', $token);
            $this->notifier->passwordReset($user, $url);

            if (config('immora.sms.driver') === 'log') {
                $request->session()->flash('demo', "Mode démonstration : le lien est écrit dans le journal (storage/logs/laravel.log).");
            }
        }

        return back()->with('status', self::NEUTRAL);
    }

    public function edit(string $token): View|RedirectResponse
    {
        if ($this->findValid($token) === null) {
            return redirect()->route('password.request')
                ->with('error', 'Ce lien a expiré ou a déjà été utilisé. Demandez-en un nouveau.');
        }

        return view('auth.reset', ['token' => $token]);
    }

    public function update(Request $request, string $token): RedirectResponse
    {
        $reset = $this->findValid($token);
        if ($reset === null) {
            return redirect()->route('password.request')
                ->with('error', 'Ce lien a expiré ou a déjà été utilisé. Demandez-en un nouveau.');
        }

        $user = $reset->user;
        $request->validate(['password' => StrongPassword::rules([$user->first_name, $user->last_name, $user->email])]);

        DB::transaction(function () use ($user, $reset, $request): void {
            $user->forceFill([
                'password' => $request->input('password'),
                'status' => $user->status === 'locked' ? 'active' : $user->status,
                'failed_logins' => 0,
                'locked_until' => null,
                'remember_token' => Str::random(60),
            ])->save();
            $reset->update(['used_at' => now()]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
        });

        AuthEvent::log('password_reset', $user->id);

        return redirect()->route('login')->with('success', 'Mot de passe modifié. Toutes vos autres sessions ont été fermées. Connectez-vous.');
    }

    private function findValid(string $token): ?PasswordReset
    {
        return PasswordReset::query()->where('token_hash', hash('sha256', $token))
            ->whereNull('used_at')->where('expires_at', '>', now())->first();
    }
}
