<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Services\Auth\SessionStarter;
use App\Support\Phone;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Connexion sans mot de passe par code SMS (AUTH-02, AUTH-10).
 * La réponse est identique que le numéro soit connu ou non.
 */
final class OtpLoginController extends Controller
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly SessionStarter $starter,
    ) {}

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:30']], [
            'phone.required' => 'Saisissez votre numéro de téléphone.',
        ]);

        $phone = Phone::normalize($data['phone']);
        if ($phone === null) {
            return back()->withInput()->withErrors(['phone' => 'Ce numéro semble incomplet. Exemple : 07 01 02 03 04.']);
        }

        $user = User::query()->where('phone_e164', $phone)->first();
        $request->session()->put('otp', ['user_id' => $user?->id, 'phone' => $phone, 'sent_at' => now()->timestamp]);

        if ($user !== null && ! $user->isLocked() && $user->status !== 'disabled') {
            try {
                $code = $this->otp->send($user, 'login');
                if (config('immora.sms.driver') === 'log') {
                    $request->session()->flash('demo', "Mode démonstration : aucun SMS n'est envoyé. Votre code est {$code}.");
                }
            } catch (DomainException $exception) {
                return back()->withInput()->withErrors(['phone' => $exception->getMessage()]);
            }
        }

        return redirect()->route('otp.show');
    }

    public function show(Request $request): View|RedirectResponse
    {
        $otp = $request->session()->get('otp');
        if ($otp === null) {
            return redirect()->route('login', ['mode' => 'sms']);
        }

        $elapsed = now()->timestamp - (int) $otp['sent_at'];

        return view('auth.otp', [
            'masked' => Phone::mask($otp['phone']),
            'phone' => $otp['phone'],
            'resendIn' => max(0, 60 - $elapsed),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:12']], [
            'code.required' => 'Saisissez le code reçu par SMS.',
        ]);

        $otp = $request->session()->get('otp');
        $user = isset($otp['user_id']) ? User::query()->find($otp['user_id']) : null;

        if ($user === null) {
            return back()->withErrors(['code' => 'Code incorrect. Vérifiez le SMS reçu ou demandez un nouveau code.']);
        }

        $result = $this->otp->verify($user, 'login', $data['code']);
        if (! $result['ok']) {
            return back()->withErrors(['code' => $result['message']]);
        }

        $request->session()->forget('otp');
        $user->forceFill(['phone_verified_at' => $user->phone_verified_at ?? now()])->save();

        return $this->starter->begin($user, $otp['phone'], $request, false, 'otp');
    }
}
