<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\User;
use App\Support\CurrentAgency;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\BuildsAgency;
use Tests\TestCase;

/**
 * Authentification (CDC chap. 8) : connexion, messages neutres, verrouillage, OTP, 2FA, cloisonnement.
 */
final class AuthenticationTest extends TestCase
{
    use BuildsAgency, RefreshDatabase;

    public function test_login_page_renders_both_methods(): void
    {
        $this->get('/connexion')->assertOk()->assertSee('Bon retour parmi nous')->assertSee('Code par SMS');
    }

    public function test_staff_logs_in_with_email_or_phone(): void
    {
        $agency = $this->makeAgency();
        $user = $this->makeMember($agency);

        $this->post('/connexion', ['identifier' => $user->email, 'password' => UserFactory::PASSWORD])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        auth()->logout();
        $national = substr($user->phone_e164, 4);
        $this->post('/connexion', ['identifier' => $national, 'password' => UserFactory::PASSWORD])
            ->assertRedirect(route('dashboard'));
    }

    public function test_wrong_password_and_unknown_user_get_the_same_neutral_message(): void
    {
        $agency = $this->makeAgency();
        $user = $this->makeMember($agency);
        $message = 'Identifiant ou mot de passe incorrect. Vérifiez votre saisie ou utilisez la connexion par code SMS.';

        $this->post('/connexion', ['identifier' => $user->email, 'password' => 'mauvais'])->assertSessionHasErrors(['identifier' => $message]);
        $this->post('/connexion', ['identifier' => 'inconnu@nulle-part.ci', 'password' => 'mauvais'])->assertSessionHasErrors(['identifier' => $message]);
        $this->assertGuest();
    }

    public function test_account_is_locked_15_minutes_after_five_failures(): void
    {
        $agency = $this->makeAgency();
        $user = $this->makeMember($agency);

        for ($i = 0; $i < 5; $i++) {
            RateLimiter::clear('login-id:'.sha1(mb_strtolower($user->email)));
            $this->post('/connexion', ['identifier' => $user->email, 'password' => 'mauvais-'.$i]);
        }

        $user->refresh();
        $this->assertSame(5, $user->failed_logins);
        $this->assertTrue($user->locked_until->isFuture());

        RateLimiter::clear('login-id:'.sha1(mb_strtolower($user->email)));
        $this->post('/connexion', ['identifier' => $user->email, 'password' => UserFactory::PASSWORD])
            ->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_tenant_logs_in_with_sms_code_and_lands_on_portal(): void
    {
        $agency = $this->makeAgency();
        ['tenant' => $tenant] = $this->makeLease($agency, now()->toDateString());
        $user = User::create(['first_name' => 'Fatou', 'last_name' => 'Bamba', 'phone_e164' => $tenant->phone_e164, 'last_agency_id' => $agency->id]);
        $agency->users()->attach($user->id, ['role' => 'tenant', 'is_active' => true]);
        $tenant->update(['user_id' => $user->id]);
        app(CurrentAgency::class)->clear();

        $this->post('/connexion/code', ['phone' => $tenant->phone_e164])->assertRedirect(route('otp.show'));
        preg_match('/(\d{6})\.$/', (string) session('demo'), $matches);
        $this->assertNotEmpty($matches, 'Le code est affiché en mode démonstration');
        $this->assertSame(1, OtpCode::query()->where('user_id', $user->id)->count());

        $this->post('/connexion/code/verifier', ['code' => $matches[1] === '000000' ? '111111' : '000000'])->assertSessionHasErrors('code');
        $this->post('/connexion/code/verifier', ['code' => $matches[1]])->assertRedirect(route('portal.home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_must_set_up_two_factor_when_enforced(): void
    {
        $agency = $this->makeAgency(['enforce_two_factor' => true]);
        $user = $this->makeMember($agency, 'admin');

        $this->actingAs($user)->withSession(['agency_id' => $agency->id, 'last_activity_at' => now()->timestamp])
            ->get('/tableau-de-bord')->assertRedirect(route('two-factor.setup'));
    }

    public function test_user_cannot_open_a_resource_of_another_agency(): void
    {
        $agencyA = $this->makeAgency();
        $agencyB = $this->makeAgency();
        $userA = $this->makeMember($agencyA);
        ['lease' => $leaseB, 'tenant' => $tenantB] = $this->makeLease($agencyB, now()->toDateString());
        app(CurrentAgency::class)->clear();

        $session = ['agency_id' => $agencyA->id, 'last_activity_at' => now()->timestamp];
        $this->actingAs($userA)->withSession($session)->get(route('leases.show', $leaseB->id))->assertNotFound();
        $this->actingAs($userA)->withSession($session)->get(route('tenants.show', $tenantB->id))->assertNotFound();
    }

    public function test_deactivated_member_loses_access(): void
    {
        $agency = $this->makeAgency();
        $admin = $this->makeMember($agency);
        $member = $this->makeMember($agency, 'manager');
        $session = ['agency_id' => $agency->id, 'last_activity_at' => now()->timestamp];

        $this->actingAs($admin)->withSession($session)->post(route('team.toggle', $member))->assertRedirect();

        $this->actingAs($member)->withSession($session)->get('/tableau-de-bord')->assertRedirect(route('agency.select'));
    }
}
