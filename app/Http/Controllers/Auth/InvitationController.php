<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthEvent;
use App\Models\Invitation;
use App\Models\User;
use App\Rules\StrongPassword;
use App\Services\Auth\SessionStarter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Première connexion d'un collaborateur invité (AUTH-09) : lien valable 72 h.
 */
final class InvitationController extends Controller
{
    public function __construct(private readonly SessionStarter $starter) {}

    public function show(string $token): View|RedirectResponse
    {
        $invitation = $this->find($token);
        if ($invitation === null) {
            return redirect()->route('login')->with('error', 'Cette invitation a expiré ou a déjà été utilisée. Demandez-en une nouvelle à votre administrateur.');
        }

        return view('auth.invitation', ['invitation' => $invitation, 'token' => $token]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->find($token);
        if ($invitation === null) {
            return redirect()->route('login')->with('error', 'Cette invitation a expiré ou a déjà été utilisée.');
        }

        $request->validate([
            'password' => StrongPassword::rules([$invitation->first_name, $invitation->last_name, $invitation->email, $invitation->agency->name]),
        ]);

        $user = DB::transaction(function () use ($invitation, $request): User {
            $user = User::query()
                ->when($invitation->email, fn ($q) => $q->where('email', $invitation->email))
                ->when(! $invitation->email, fn ($q) => $q->where('phone_e164', $invitation->phone_e164))
                ->first();

            $user ??= User::create([
                'first_name' => $invitation->first_name,
                'last_name' => $invitation->last_name,
                'email' => $invitation->email,
                'phone_e164' => $invitation->phone_e164,
                'email_verified_at' => $invitation->email ? now() : null,
                'phone_verified_at' => $invitation->phone_e164 ? now() : null,
            ]);
            $user->forceFill(['password' => $request->input('password'), 'last_agency_id' => $invitation->agency_id])->save();

            $invitation->agency->users()->syncWithoutDetaching([$user->id => ['role' => $invitation->role, 'is_active' => true]]);
            $invitation->update(['accepted_at' => now()]);

            return $user;
        });

        AuthEvent::log('invitation_accepted', $user->id, [], $invitation->agency_id);

        return $this->starter->complete($user, (string) ($user->email ?? $user->phone_e164), $request, false, 'invitation');
    }

    private function find(string $token): ?Invitation
    {
        $invitation = Invitation::query()->where('token_hash', hash('sha256', $token))->with('agency', 'inviter')->first();

        return $invitation?->isUsable() ? $invitation : null;
    }
}
