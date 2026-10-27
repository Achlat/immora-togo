<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Agency;
use App\Models\Lease;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Support\CurrentAgency;
use Database\Seeders\PlanSeeder;

/**
 * Fabrique d'une agence minimale : propriétaire sous mandat, bien, lot, locataire, bail en brouillon.
 */
trait BuildsAgency
{
    protected function makeAgency(array $settings = []): Agency
    {
        $this->seed(PlanSeeder::class);
        $agency = Agency::create([
            'name' => 'Agence Test '.uniqid(), 'slug' => uniqid('a'), 'country_code' => 'CI', 'currency' => 'XOF', 'vat_rate' => 18,
            'status' => 'active', 'settings' => $settings + ['enforce_two_factor' => false, 'payout_dual_control_amount' => 1000000],
        ]);
        $agency->subscriptions()->create([
            'plan_id' => Plan::query()->where('code', 'business')->value('id'), 'status' => 'active',
            'current_period_end' => now()->addYear()->toDateString(),
        ]);

        return $agency;
    }

    protected function makeMember(Agency $agency, string $role = 'admin'): User
    {
        $user = User::factory()->create(['last_agency_id' => $agency->id]);
        $agency->users()->attach($user->id, ['role' => $role, 'is_active' => true]);

        return $user;
    }

    protected function actAs(Agency $agency): void
    {
        app(CurrentAgency::class)->set($agency, 'system');
    }

    /** @return array{owner: Owner, unit: Unit, tenant: Tenant, lease: Lease} */
    protected function makeLease(Agency $agency, string $start, int $rent = 150000, array $overrides = []): array
    {
        $this->actAs($agency);
        $owner = Owner::create(['agency_id' => $agency->id, 'reference' => uniqid('PRO'), 'first_name' => 'Mamadou', 'last_name' => 'Diallo']);
        $owner->mandate()->create([
            'agency_id' => $agency->id, 'reference' => uniqid('MAN'), 'start_date' => '2026-01-01',
            'commission_rate' => 10, 'commission_vat' => true, 'commission_base' => 'rent', 'reserve_amount' => 50000,
        ]);
        $property = Property::create(['agency_id' => $agency->id, 'owner_id' => $owner->id, 'reference' => uniqid('BIE'),
            'name' => 'Résidence Test', 'type' => 'immeuble', 'city' => 'Abidjan']);
        $unit = Unit::create(['agency_id' => $agency->id, 'property_id' => $property->id, 'reference' => uniqid('LOT'),
            'label' => 'A01', 'type' => 'appartement', 'base_rent_amount' => $rent]);
        $tenant = Tenant::create(['agency_id' => $agency->id, 'reference' => uniqid('LOC'), 'first_name' => 'Fatou',
            'last_name' => 'Bamba', 'phone_e164' => '+22507'.random_int(10000000, 99999999)]);
        $lease = Lease::create($overrides + [
            'agency_id' => $agency->id, 'unit_id' => $unit->id, 'tenant_id' => $tenant->id, 'reference' => uniqid('BAIL'),
            'start_date' => $start, 'rent_amount' => $rent, 'charges_amount' => 0, 'vat_rate' => 0,
            'due_day' => 5, 'grace_days' => 5, 'penalty_rate' => 10,
            'deposit_amount' => $rent * 2, 'advance_amount' => $rent * 2, 'agency_fee_amount' => $rent, 'status' => 'draft',
        ]);

        return compact('owner', 'unit', 'tenant', 'lease');
    }
}
