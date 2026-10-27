<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Agency;
use App\Services\Billing\LeaseBillingService;
use App\Support\CurrentAgency;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Appels de loyers : à partir du 25, génère les échéances du mois suivant (CDC chap. 13).
 */
final class GenerateInvoices extends Command
{
    protected $signature = 'immora:invoices {--month= : Mois à générer, format AAAA-MM}';

    protected $description = 'Génère les échéances mensuelles de tous les baux actifs';

    public function handle(LeaseBillingService $billing, CurrentAgency $current): int
    {
        $month = $this->option('month')
            ? Carbon::createFromFormat('Y-m', $this->option('month'))->startOfMonth()
            : (now()->day >= 25 ? now()->addMonthNoOverflow()->startOfMonth() : now()->startOfMonth());

        $total = 0;
        foreach (Agency::query()->whereIn('status', ['trial', 'active'])->get() as $agency) {
            $current->set($agency, 'system');
            $count = $billing->generateForMonth($month);
            $total += $count;
            $this->line("{$agency->name} : {$count} échéance(s)");
        }
        $current->clear();

        $this->info("{$total} échéance(s) générée(s) pour ".$month->translatedFormat('F Y').'.');

        return self::SUCCESS;
    }
}
