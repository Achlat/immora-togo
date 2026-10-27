<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\OwnerStatement;
use App\Models\Payment;
use App\Models\PaymentIntent;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payments\OnlinePaymentService;
use App\Support\CurrentAgency;
use Database\Seeders\DemoSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chaque écran du back-office, des portails et des PDF répond sans erreur sur l'agence de démonstration.
 */
final class ScreensSmokeTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DemoSeeder::class]);
        $this->agency = Agency::query()->where('slug', 'horizon')->firstOrFail();
    }

    public function test_every_back_office_screen_renders(): void
    {
        $admin = User::query()->where('email', 'awa@horizon-immo.ci')->firstOrFail();
        app(CurrentAgency::class)->set($this->agency, 'admin');
        $property = Property::query()->first();
        $unit = $property->units()->first();
        $lease = Lease::query()->where('status', 'active')->first();
        $draft = Lease::query()->where('status', 'draft')->first();
        $tenant = Tenant::query()->first();
        $invoice = Invoice::query()->where('status', 'paid')->first();
        $payment = Payment::query()->first();
        $statement = OwnerStatement::query()->first();
        app(CurrentAgency::class)->clear();

        $urls = [
            route('dashboard'), route('properties.index'), route('properties.show', $property), route('properties.create'),
            route('properties.edit', $property), route('units.edit', $unit),
            route('owners.index'), route('owners.create'), route('owners.show', $property->owner_id), route('owners.edit', $property->owner_id),
            route('tenants.index'), route('tenants.create'), route('tenants.show', $tenant), route('tenants.edit', $tenant),
            route('leases.index'), route('leases.create'), route('leases.show', $lease), route('leases.show', $draft),
            route('invoices.index'), route('invoices.index', ['filter' => 'overdue']), route('invoices.show', $invoice),
            route('payments.index'), route('payments.create'), route('payments.create', ['tenant' => $tenant->id]), route('payments.show', $payment),
            route('cash.index'), route('expenses.index'), route('expenses.create'),
            route('statements.index'), route('statements.show', $statement), route('reports.arrears'),
            route('team.index'), route('settings.edit'), route('security.index'),
            route('search', ['q' => 'Bamba']),
        ];

        $session = ['agency_id' => $this->agency->id, 'last_activity_at' => now()->timestamp];
        foreach ($urls as $url) {
            $this->actingAs($admin)->withSession($session)->get($url)->assertOk();
        }

        foreach ([route('invoices.pdf', $invoice), route('payments.pdf', $payment), route('statements.pdf', $statement)] as $pdf) {
            $this->actingAs($admin)->withSession($session)->get($pdf)->assertOk()->assertHeader('content-type', 'application/pdf');
        }
    }

    public function test_tenant_and_owner_portals_render_and_online_payment_is_idempotent(): void
    {
        $tenantUser = User::query()->where('phone_e164', '+2250707070707')->firstOrFail();
        $ownerUser = User::query()->where('phone_e164', '+33612345678')->firstOrFail();
        $session = ['agency_id' => $this->agency->id, 'last_activity_at' => now()->timestamp];

        $this->actingAs($tenantUser)->withSession($session)->get(route('portal.home'))->assertOk()->assertSee('Mon logement');
        $this->actingAs($ownerUser)->withSession($session)->get(route('portal.home'))->assertOk()->assertSee('Mon patrimoine');

        app(CurrentAgency::class)->set($this->agency, 'system');
        $tenant = Tenant::query()->where('user_id', $tenantUser->id)->firstOrFail();
        app(CurrentAgency::class)->clear();
        $intent = PaymentIntent::create([
            'agency_id' => $this->agency->id, 'tenant_id' => $tenant->id, 'reference' => 'IMMTEST0001', 'provider' => 'fake', 'amount' => 50000,
            'provider_payload' => ['simulated_status' => 'accepted', 'simulated_operator' => 'wave', 'simulated_ref' => 'WAVE-TEST-1'],
        ]);

        $service = app(OnlinePaymentService::class);
        $first = $service->confirm($intent);
        $second = $service->confirm($intent->refresh());

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second->id, 'Un webhook reçu deux fois n\'enregistre qu\'un paiement (PAY-02)');
        $this->assertSame(1, Payment::withoutGlobalScopes()->where('external_ref', 'WAVE-TEST-1')->count());

        $this->get(route('verify.show', $first->verify_code))->assertOk()->assertSee('Document authentique');
    }
}
