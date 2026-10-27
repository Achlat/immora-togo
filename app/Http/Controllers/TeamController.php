<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\AuthEvent;
use App\Models\Invitation;
use App\Models\User;
use App\Services\Messaging\Notifier;
use App\Support\CurrentAgency;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Équipe : invitations (AUTH-09) et désactivation immédiate (AUTH-19). */
final class TeamController extends Controller
{
    public function index(CurrentAgency $current): View
    {
        $agency = $current->get();

        return view('team.index', [
            'members' => $agency->users()->wherePivotIn('role', config('immora.staff_roles'))->orderBy('first_name')->get(),
            'invitations' => Invitation::query()->where('agency_id', $agency->id)->whereNull('accepted_at')
                ->where('expires_at', '>', now())->latest()->get(),
            'roles' => array_intersect_key(config('immora.roles'), array_flip(config('immora.staff_roles'))),
        ]);
    }

    public function invite(Request $request, Notifier $notifier, CurrentAgency $current): RedirectResponse
    {
        $agency = $current->get();
        $request->merge(['phone_e164' => Phone::normalize($request->input('phone'), $agency->country_code)]);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['nullable', 'required_without:phone_e164', 'email', 'max:190'],
            'phone_e164' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(config('immora.staff_roles'))],
        ], ['email.required_without' => 'Indiquez un e-mail ou un numéro de téléphone valide.']);

        $plan = $agency->subscription?->plan;
        $members = $agency->users()->wherePivotIn('role', config('immora.staff_roles'))->wherePivot('is_active', true)->count();
        if ($plan?->max_users !== null && $members >= $plan->max_users) {
            return back()->withInput()->with('error', "Votre offre {$plan->name} est limitée à {$plan->max_users} utilisateurs. Passez à l'offre supérieure pour inviter plus de collaborateurs.");
        }

        $token = Str::random(64);
        Invitation::create([
            'agency_id' => $agency->id,
            'invited_by' => $request->user()->id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => isset($data['email']) ? mb_strtolower($data['email']) : null,
            'phone_e164' => $data['phone_e164'] ?? null,
            'role' => $data['role'],
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHours(config('immora.security.invitation_ttl_hours')),
        ]);

        $url = route('invitation.show', $token);
        $byPhone = empty($data['email']);
        $notifier->invitation($byPhone ? $data['phone_e164'] : mb_strtolower($data['email']), $byPhone,
            $request->user()->fullName(), $agency->name, $url, $agency->id);

        $flash = back()->with('success', "Invitation envoyée à {$data['first_name']} {$data['last_name']} (valable 72 h).");

        return config('immora.sms.driver') === 'log' ? $flash->with('demo', "Mode démonstration — lien d'invitation : {$url}") : $flash;
    }

    public function toggle(Request $request, User $user, CurrentAgency $current): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 422, 'Vous ne pouvez pas désactiver votre propre compte.');
        $agency = $current->get();
        $membership = $agency->users()->where('users.id', $user->id)->first();
        abort_if($membership === null, 404);

        $active = ! $membership->pivot->is_active;
        $agency->users()->updateExistingPivot($user->id, ['is_active' => $active]);

        if (! $active) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->forceFill(['remember_token' => Str::random(60)])->save();
            AuthEvent::log('sessions_revoked', $user->id, ['reason' => 'deactivated'], $agency->id);
        }
        AuditLog::record($active ? 'member.activated' : 'member.deactivated', $user);

        return back()->with('success', $active ? "{$user->fullName()} a de nouveau accès." : "Accès de {$user->fullName()} révoqué immédiatement. Ses données sont conservées.");
    }
}
