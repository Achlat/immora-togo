<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Console\Command;

final class CreateTogoDemo extends Command
{
    protected $signature = 'demo:create-togo';
    protected $description = 'Create Togo demo agency and admin account';

    public function handle(): int
    {
        $agency = Agency::updateOrCreate(
            ['slug' => 'prestige-togo'],
            [
                'name' => 'Prestige Immobilière Togo',
                'country_code' => 'TG',
                'currency' => 'XOF',
                'phone' => '+22890112233',
                'email' => 'contact@prestige-immo.tg',
                'address' => 'Avenue de la Libération, Adidogomé',
                'city' => 'Lomé',
                'rccm' => 'TG-LOM-2024-B-00152',
                'tax_id' => '2024-0458-TG',
                'license_number' => 'LIC-IMM-2024-001',
                'vat_rate' => 18,
                'status' => 'active',
            ]
        );

        $agency->subscriptions()->updateOrCreate(
            ['plan_id' => Plan::where('code', 'business')->value('id')],
            ['status' => 'active', 'billing_cycle' => 'yearly', 'current_period_end' => now()->addYear()]
        );

        $user = User::updateOrCreate(
            ['email' => 'kofi@prestige-immo.tg'],
            [
                'first_name' => 'Kofi',
                'last_name' => 'Mensah',
                'password' => 'Togo-Demo-2026',
                'email_verified_at' => now(),
                'last_agency_id' => $agency->id,
            ]
        );

        $agency->users()->syncWithoutDetaching([$user->id => ['role' => 'admin', 'is_active' => true]]);

        $this->info("✅ Togo demo created!");
        $this->info("📧 kofi@prestige-immo.tg");
        $this->info("🔐 Togo-Demo-2026");

        return 0;
    }
}
