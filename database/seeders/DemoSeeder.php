<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\CashSession;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\Billing\LeaseBillingService;
use App\Services\Billing\PaymentService;
use App\Services\Billing\PenaltyService;
use App\Services\Billing\StatementService;
use App\Services\SequenceService;
use App\Support\CurrentAgency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Agence de démonstration « Prestige Immobilière Togo » (Lomé) avec 12 mois d'historique réaliste :
 * baux, encaissements, impayés, dépenses, relevés. Sert aux démonstrations commerciales.
 */
final class DemoSeeder extends Seeder
{
    public const PASSWORD = 'Horizon-Demo-2026';

    private Agency $agency;

    private User $cashier;

    public function __construct(
        private readonly SequenceService $sequences,
        private readonly LeaseBillingService $billing,
        private readonly PaymentService $payments,
        private readonly PenaltyService $penalties,
        private readonly StatementService $statements,
        private readonly CurrentAgency $current,
    ) {}

    public function run(): void
    {
        User::query()->updateOrCreate(['email' => 'admin@immora.app'], [
            'first_name' => 'Équipe', 'last_name' => 'Immora', 'password' => 'Immora-Admin-2026', 'is_super_admin' => true,
        ]);

        $this->agency = Agency::create([
            'name' => 'Prestige Immobilière Togo', 'slug' => 'prestige-togo', 'country_code' => 'TG', 'currency' => 'XOF',
            'phone' => '+22890112233', 'email' => 'contact@prestige-immo.tg', 'address' => 'Avenue de la Libération, Quartier Adidogomé',
            'city' => 'Lomé', 'rccm' => 'TG-LOM-2024-B-00152', 'tax_id' => '2024-0458-TG', 'license_number' => 'LIC-IMM-2024-001',
            'vat_rate' => 18, 'status' => 'active',
            'settings' => ['due_day' => 5, 'grace_days' => 5, 'penalty_rate' => 10, 'default_deposit_months' => 2,
                'default_advance_months' => 2, 'payout_dual_control_amount' => 1000000, 'online_fee_payer' => 'agency',
                'enforce_two_factor' => false],
        ]);
        $this->agency->subscriptions()->create([
            'plan_id' => Plan::query()->where('code', 'business')->value('id'),
            'status' => 'active', 'billing_cycle' => 'yearly', 'current_period_end' => now()->addYear()->toDateString(),
        ]);
        $this->current->set($this->agency, 'system');

        $awa = $this->staff('Awa', 'Agbègnon', 'awa@prestige-immo.tg', '+22891234567', 'admin');
        $this->staff('Koffi', 'Mensah', 'koffi@prestige-immo.tg', '+22892345678', 'manager');
        $this->cashier = $this->staff('Mariam', 'Kpogo', 'mariam@prestige-immo.tg', '+22893456789', 'accountant');

        $owners = $this->owners();
        $units = $this->properties($owners);
        $this->leasesAndHistory($units);
        $this->expenses($owners);

        $this->billing->generateForMonth(now()->addMonthNoOverflow()->startOfMonth());
        $this->penalties->apply(now()->startOfDay());
        $this->statementsAndPayouts($owners, $awa);
        $this->portalAccounts($owners['diallo']);

        $this->current->clear();
    }

    private function staff(string $first, string $last, string $email, string $phone, string $role): User
    {
        $user = User::create([
            'first_name' => $first, 'last_name' => $last, 'email' => $email, 'phone_e164' => $phone,
            'password' => self::PASSWORD, 'email_verified_at' => now(), 'last_agency_id' => $this->agency->id,
        ]);
        $this->agency->users()->attach($user->id, ['role' => $role, 'is_active' => true]);

        return $user;
    }

    /** @return array<string, Owner> */
    private function owners(): array
    {
        $data = [
            'diallo' => ['first_name' => 'Yaovi', 'last_name' => 'Dossavi', 'phone_e164' => '+22891111111', 'whatsapp_e164' => '+22891111111',
                'email' => 'yaovi.dossavi@prestige.tg', 'country_of_residence' => 'TG', 'payout_method' => 'transfer', 'payout_details' => 'TG001-0012-345678-9',
                'mandate' => ['commission_rate' => 10, 'reserve_amount' => 50000, 'expense_ceiling_amount' => 150000]],
            'kouassi' => ['first_name' => 'Akossiwa', 'last_name' => 'Mensah', 'phone_e164' => '+22892222222', 'whatsapp_e164' => '+22892222222',
                'payout_method' => 'mobile_money', 'payout_details' => 'YAS Togo +228 92 22 22 22',
                'mandate' => ['commission_rate' => 8, 'reserve_amount' => 0, 'expense_ceiling_amount' => 50000]],
            'cocotiers' => ['kind' => 'company', 'company_name' => 'Prestige Immobilière Développement', 'phone_e164' => '+22893333333', 'email' => 'gestion@prestige-dev.tg',
                'payout_method' => 'transfer', 'payout_details' => 'TG002-0045-678901-2',
                'mandate' => ['commission_rate' => 7, 'reserve_amount' => 200000, 'expense_ceiling_amount' => 500000]],
            'bamba' => ['first_name' => 'Kofi', 'last_name' => 'Afavi', 'phone_e164' => '+22894444444',
                'payout_method' => 'mobile_money', 'payout_details' => 'Orange Money +228 94 44 44 44',
                'mandate' => ['commission_rate' => 10, 'reserve_amount' => 0, 'expense_ceiling_amount' => 100000]],
        ];

        $owners = [];
        foreach ($data as $key => $attributes) {
            $mandate = $attributes['mandate'];
            unset($attributes['mandate']);
            $owner = Owner::create($attributes + ['agency_id' => $this->agency->id, 'reference' => $this->sequences->reference($this->agency->id, 'owner')]);
            $owner->mandate()->create($mandate + [
                'agency_id' => $this->agency->id, 'reference' => $this->sequences->reference($this->agency->id, 'mandate'),
                'start_date' => '2025-09-01', 'duration_months' => 36, 'commission_vat' => true, 'commission_base' => 'rent', 'payout_day' => 10,
            ]);
            $owners[$key] = $owner;
        }

        return $owners;
    }

    /** @param array<string, Owner> $owners @return list<Unit> */
    private function properties(array $owners): array
    {
        $buildings = [
            ['owner' => 'diallo', 'name' => 'Résidence Bè-Plage', 'type' => 'immeuble', 'district' => 'Bè-Plage',
                'landmark' => 'Près de la Cathédrale', 'units' => [['A', 6, 'appartement', 3, [250000, 275000, 300000, 300000, 325000, 350000], 15000]]],
            ['owner' => 'kouassi', 'name' => 'Cour Adidogomé', 'type' => 'cour_commune', 'district' => 'Adidogomé',
                'landmark' => 'Proche Marché Adidogomé', 'units' => [['CH', 6, 'chambre_salon', 2, [45000, 45000, 50000, 50000, 55000, 60000], 0]]],
            ['owner' => 'cocotiers', 'name' => 'Centre Commercial Agoé', 'type' => 'commercial', 'district' => 'Agoé-Nyive',
                'landmark' => 'Avenue de la Paix, près Pharmacie Centrale', 'units' => [['B', 3, 'bureau', 4, [450000, 550000, 650000], 50000], ['M', 2, 'magasin', 1, [300000, 350000], 0]]],
            ['owner' => 'bamba', 'name' => 'Villa Tokoin Prestige', 'type' => 'villa', 'district' => 'Tokoin',
                'landmark' => 'Près du Lycée Tokoin', 'units' => [['V', 1, 'villa_basse', 5, [900000], 0]]],
        ];

        $units = [];
        foreach ($buildings as $building) {
            $property = Property::create([
                'agency_id' => $this->agency->id, 'owner_id' => $owners[$building['owner']]->id,
                'reference' => $this->sequences->reference($this->agency->id, 'property'),
                'name' => $building['name'], 'type' => $building['type'], 'city' => 'Lomé',
                'district' => $building['district'], 'landmark' => $building['landmark'],
            ]);
            foreach ($building['units'] as [$prefix, $count, $type, $rooms, $rents, $charges]) {
                for ($i = 1; $i <= $count; $i++) {
                    $label = $prefix.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                    $units[] = Unit::create([
                        'agency_id' => $this->agency->id, 'property_id' => $property->id,
                        'reference' => $property->reference.'-'.$label, 'label' => $label, 'type' => $type,
                        'usage_type' => in_array($type, ['bureau', 'magasin'], true) ? 'commercial' : 'habitation',
                        'rooms' => $rooms, 'base_rent_amount' => $rents[$i - 1], 'charges_amount' => $charges,
                        'deposit_months' => $type === 'bureau' ? 3 : 2, 'advance_months' => $type === 'chambre_salon' ? 1 : 2,
                        'status' => 'vacant',
                    ]);
                }
            }
        }

        return $units;
    }

    /** @param list<Unit> $units */
    private function leasesAndHistory(array $units): void
    {
        $people = [
            ['Kofi', 'Mensah', '+228707070707'], ['Kwesi', 'Agbodjan', '+228505121212'], ['Ama', 'Dossavi', '+228102131415'],
            ['Yao', 'Agbègnon', '+228707161718'], ['Mawusi', 'Kpogo', '+228545192021'], ['Mawuvi', 'Agbaglah', '+228707222324'],
            ['Akossi', 'Tekpo', '+228102252627'], ['Kodjovi', 'Agbènyegah', '+228505282930'], ['Nadia', 'Agbozo', '+228707313233'],
            ['Didier', 'Mensah', '+228102343536'], ['Afiwa', 'Tekpo', '+228545373839'], ['Hervé', 'Agbègnon', '+228707404142'],
            ['Nadia', 'Dossavi', '+228505434445'], ['Paul', 'Agbodjan', '+228707464748'],
        ];
        $companies = ['Cabinet Juridique Togo SARL', 'Orange Business Partner TG', 'Pharmacie Centrale Togo'];

        // [index du lot, date d'entrée, profil de paiement]
        $plan = [
            [0, '2025-11-12', 'good'], [1, '2026-01-01', 'good'], [2, '2026-02-15', 'late'], [3, '2025-12-01', 'good'],
            [4, '2026-05-01', 'arrears'], [6, '2025-12-01', 'good'], [7, '2026-03-10', 'partial'], [8, '2026-01-01', 'arrears'],
            [9, '2026-06-01', 'good'], [10, '2026-04-01', 'good'], [12, '2025-12-01', 'good'], [13, '2026-02-01', 'late'],
            [14, '2026-07-01', 'good'], [15, '2026-03-01', 'good'], [17, '2025-11-01', 'good'],
        ];

        foreach ($plan as $i => [$unitIndex, $start, $profile]) {
            $unit = $units[$unitIndex];
            $isCompany = $unit->usage_type === 'commercial' && $i % 2 === 0 && count($companies) > 0;
            $person = $people[$i % count($people)];
            $tenant = Tenant::create([
                'agency_id' => $this->agency->id,
                'reference' => $this->sequences->reference($this->agency->id, 'tenant'),
                'kind' => $isCompany ? 'company' : 'person',
                'company_name' => $isCompany ? array_shift($companies) : null,
                'first_name' => $isCompany ? null : $person[0],
                'last_name' => $isCompany ? null : $person[1],
                'phone_e164' => $person[2],
                'whatsapp_e164' => $person[2],
                'profession' => $isCompany ? null : ['Comptable', 'Enseignant', 'Infirmière', 'Commerçant', 'Ingénieur'][$i % 5],
                'monthly_income_amount' => $unit->base_rent_amount * 4,
            ]);

            $lease = Lease::create([
                'agency_id' => $this->agency->id, 'unit_id' => $unit->id, 'tenant_id' => $tenant->id,
                'reference' => $this->sequences->next($this->agency->id, 'lease', (int) substr($start, 0, 4)),
                'type' => $unit->usage_type === 'commercial' ? 'commercial' : 'habitation',
                'start_date' => $start, 'duration_months' => $unit->usage_type === 'commercial' ? 36 : 12,
                'end_date' => Carbon::parse($start)->addMonths($unit->usage_type === 'commercial' ? 36 : 12)->subDay()->toDateString(),
                'rent_amount' => $unit->base_rent_amount, 'charges_amount' => $unit->charges_amount,
                'vat_rate' => $unit->usage_type === 'commercial' ? 18 : 0,
                'due_day' => 5, 'grace_days' => 5, 'penalty_rate' => 10,
                'deposit_amount' => $unit->base_rent_amount * $unit->deposit_months,
                'advance_amount' => $unit->base_rent_amount * $unit->advance_months,
                'agency_fee_amount' => $unit->base_rent_amount,
                'status' => 'draft',
            ]);
            $this->billing->activate($lease);
            $this->payHistory($tenant, $lease->refresh(), $profile, $i);
        }

        // Un bail en brouillon prêt à être activé pendant la démo.
        $draftTenant = Tenant::create([
            'agency_id' => $this->agency->id, 'reference' => $this->sequences->reference($this->agency->id, 'tenant'),
            'first_name' => 'Akossiwa', 'last_name' => 'Kpogo', 'phone_e164' => '+228707494950', 'profession' => 'Pharmacienne',
        ]);
        Lease::create([
            'agency_id' => $this->agency->id, 'unit_id' => $units[5]->id, 'tenant_id' => $draftTenant->id,
            'reference' => $this->sequences->next($this->agency->id, 'lease'), 'type' => 'habitation',
            'start_date' => now()->addDays(7)->toDateString(), 'duration_months' => 12,
            'end_date' => now()->addDays(7)->addYear()->subDay()->toDateString(),
            'rent_amount' => $units[5]->base_rent_amount, 'charges_amount' => $units[5]->charges_amount, 'vat_rate' => 0,
            'due_day' => 5, 'grace_days' => 5, 'penalty_rate' => 10,
            'deposit_amount' => $units[5]->base_rent_amount * 2, 'advance_amount' => $units[5]->base_rent_amount * 2,
            'agency_fee_amount' => $units[5]->base_rent_amount, 'status' => 'draft',
        ]);
    }

    private function payHistory(Tenant $tenant, Lease $lease, string $profile, int $seed): void
    {
        $methods = [['mobile_money', 'wave'], ['mobile_money', 'orange'], ['cash', null], ['transfer', null], ['mobile_money', 'mtn']];
        $invoices = Invoice::query()->where('lease_id', $lease->id)->orderBy('due_date')->get();
        $count = $invoices->count();

        foreach ($invoices as $index => $invoice) {
            $invoice->refresh();
            if ($invoice->balance() <= 0 || $invoice->due_date->isFuture()) {
                continue;
            }
            $isRecent = $index >= $count - 2;
            if ($profile === 'arrears' && $isRecent && $invoice->kind === 'rent') {
                continue;
            }
            if ($profile === 'late' && $index === $count - 1 && $invoice->kind === 'rent') {
                continue;
            }

            [$method, $provider] = $methods[($seed + $index) % count($methods)];
            $amount = $profile === 'partial' && $isRecent && $invoice->kind === 'rent' ? intdiv($invoice->balance(), 2) : $invoice->balance();
            $paidAt = $invoice->due_date->copy()->addDays(($seed + $index) % ($profile === 'late' ? 12 : 4))->setTime(10 + $index % 7, 15);
            if ($paidAt->isFuture()) {
                $paidAt = now()->subHour();
            }

            $this->payments->record($tenant, $amount, $method, [
                'provider' => $provider,
                'external_ref' => $provider ? strtoupper($provider).'-'.$invoice->number.'-'.$seed : null,
                'paid_at' => $paidAt,
                'created_by' => $this->cashier->id,
            ]);
        }
    }

    /** @param array<string, Owner> $owners */
    private function expenses(array $owners): void
    {
        $rows = [
            ['diallo', 'plomberie', 'Remplacement colonne d\'eau — appartement A03', 85000, -1],
            ['diallo', 'gardiennage', 'Gardiennage résidence — mois écoulé', 60000, -1],
            ['diallo', 'peinture', 'Rafraîchissement cage d\'escalier', 145000, -2],
            ['kouassi', 'electricite', 'Remise aux normes tableau électrique CH04', 38000, -1],
            ['cocotiers', 'climatisation', 'Entretien des climatiseurs — bureaux B01 à B03', 120000, -1],
        ];

        foreach ($rows as [$owner, $category, $label, $amount, $monthOffset]) {
            $property = Property::query()->where('owner_id', $owners[$owner]->id)->first();
            Expense::create([
                'agency_id' => $this->agency->id, 'owner_id' => $owners[$owner]->id, 'property_id' => $property->id,
                'charged_to' => 'owner', 'category' => $category, 'label' => $label, 'amount' => $amount,
                'spent_on' => now()->addMonthsNoOverflow($monthOffset)->day(18)->toDateString(),
                'supplier' => 'Ets Koné & Fils', 'status' => 'approved', 'created_by' => $this->cashier->id,
            ]);
        }
    }

    /** @param array<string, Owner> $owners */
    private function statementsAndPayouts(array $owners, User $validator): void
    {
        foreach ([2, 1] as $back) {
            $month = now()->subMonthsNoOverflow($back)->startOfMonth();
            foreach ($owners as $key => $owner) {
                $statement = $this->statements->generate($owner->refresh(), $month, $this->cashier);
                if ($back === 2 || $key !== 'diallo') {
                    $this->statements->validate($statement, $validator);
                    $this->statements->recordPayout($statement, $owner->payout_method, 'Ordre de virement groupé');
                }
            }
        }

        CashSession::create([
            'agency_id' => $this->agency->id, 'user_id' => $this->cashier->id, 'opening_amount' => 50000, 'opened_at' => now()->startOfDay()->addHours(8),
        ]);
    }

    private function portalAccounts(Owner $diallo): void
    {
        $fatou = Tenant::query()->where('phone_e164', '+228707070707')->first();
        foreach ([[$fatou, 'tenant'], [$diallo, 'owner']] as [$model, $role]) {
            $user = User::create([
                'first_name' => $model->first_name, 'last_name' => $model->last_name,
                'phone_e164' => $model->phone_e164, 'phone_verified_at' => now(), 'last_agency_id' => $this->agency->id,
            ]);
            $this->agency->users()->attach($user->id, ['role' => $role, 'is_active' => true]);
            $model->update(['user_id' => $user->id]);
        }
    }
}
