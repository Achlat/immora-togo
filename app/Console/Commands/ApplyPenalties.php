<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Agency;
use App\Services\Billing\PenaltyService;
use App\Support\CurrentAgency;
use Illuminate\Console\Command;

/** Pénalités de retard quotidiennes (RG-05). */
final class ApplyPenalties extends Command
{
    protected $signature = 'immora:penalties';

    protected $description = 'Applique les pénalités de retard échues';

    public function handle(PenaltyService $penalties, CurrentAgency $current): int
    {
        $total = 0;
        foreach (Agency::query()->whereIn('status', ['trial', 'active'])->get() as $agency) {
            $current->set($agency, 'system');
            $total += $penalties->apply(now()->startOfDay());
        }
        $current->clear();

        $this->info("{$total} pénalité(s) appliquée(s).");

        return self::SUCCESS;
    }
}
