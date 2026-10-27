<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/** Offres SaaS en FCFA (CDC chap. 5). Aucun tarif n'est écrit en dur dans le code. */
final class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['code' => 'essentiel', 'name' => 'Essentiel', 'monthly_price_amount' => 25000, 'yearly_price_amount' => 250000,
                'max_units' => 50, 'max_users' => 2, 'sort' => 1,
                'features' => ['properties', 'leases', 'rent', 'receipts', 'cash', 'statements', 'reports']],
            ['code' => 'pro', 'name' => 'Pro', 'monthly_price_amount' => 65000, 'yearly_price_amount' => 650000,
                'max_units' => 300, 'max_users' => 8, 'sort' => 2,
                'features' => ['properties', 'leases', 'rent', 'receipts', 'cash', 'statements', 'reports', 'online_payment', 'sms', 'portals', 'maintenance']],
            ['code' => 'business', 'name' => 'Business', 'monthly_price_amount' => 150000, 'yearly_price_amount' => 1500000,
                'max_units' => 1500, 'max_users' => 25, 'sort' => 3,
                'features' => ['properties', 'leases', 'rent', 'receipts', 'cash', 'statements', 'reports', 'online_payment', 'sms', 'portals', 'maintenance', 'crm', 'website', 'syndic', 'multi_agency', 'api']],
            ['code' => 'entreprise', 'name' => 'Entreprise', 'monthly_price_amount' => 0, 'yearly_price_amount' => 0,
                'max_units' => null, 'max_users' => null, 'sort' => 4, 'is_public' => false,
                'features' => ['*']],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(['code' => $plan['code']], $plan);
        }
    }
}
