<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Plan;
use App\Models\User;
use App\Rules\StrongPassword;
use App\Services\Auth\SessionStarter;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Inscription d'une agence avec 14 jours d'essai sur l'offre Pro (AUTH-08).
 */
final class RegisterAgencyController extends Controller
{
    public function __construct(private readonly SessionStarter $starter) {}

    public function show(): View
    {
        return view('auth.register', ['countries' => collect(config('immora.countries'))->map->name]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['phone_e164' => Phone::normalize($request->input('phone'), $request->input('country_code'))]);

        $data = $request->validate([
            'agency_name' => ['required', 'string', 'max:150'],
            'country_code' => ['required', Rule::in(array_keys(config('immora.countries')))],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'phone_e164' => ['required', 'unique:users,phone_e164'],
            'password' => StrongPassword::rules([$request->input('first_name'), $request->input('last_name'), $request->input('email'), $request->input('agency_name')]),
            'terms' => ['accepted'],
        ], [
            'phone_e164.required' => 'Ce numéro semble incomplet. Exemple : 07 01 02 03 04.',
            'phone_e164.unique' => 'Ce numéro est déjà utilisé par un compte Immora. Connectez-vous ou utilisez un autre numéro.',
            'email.unique' => 'Cette adresse est déjà utilisée par un compte Immora.',
            'terms.accepted' => 'Vous devez accepter les conditions générales d\'utilisation.',
        ]);

        $user = DB::transaction(function () use ($data): User {
            $country = config("immora.countries.{$data['country_code']}");
            $agency = Agency::create([
                'name' => $data['agency_name'],
                'slug' => $this->uniqueSlug($data['agency_name']),
                'country_code' => $data['country_code'],
                'currency' => $country['currency'],
                'phone' => $data['phone_e164'],
                'email' => mb_strtolower($data['email']),
                'status' => 'trial',
                'settings' => ['default_deposit_months' => 2, 'default_advance_months' => 2, 'due_day' => 5, 'grace_days' => 5, 'penalty_rate' => 10],
            ]);

            $plan = Plan::query()->where('code', 'pro')->firstOrFail();
            $agency->subscription()->create([
                'plan_id' => $plan->id,
                'status' => 'trial',
                'trial_ends_at' => now()->addDays(config('immora.trial_days'))->toDateString(),
            ]);

            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => mb_strtolower($data['email']),
                'phone_e164' => $data['phone_e164'],
                'password' => $data['password'],
                'last_agency_id' => $agency->id,
            ]);
            $agency->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);

            return $user;
        });

        return $this->starter->complete($user, $user->email, $request, false, 'register');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'agence';
        $slug = $base;
        $i = 2;
        while (Agency::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
