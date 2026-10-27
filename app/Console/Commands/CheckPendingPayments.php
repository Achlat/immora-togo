<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PaymentIntent;
use App\Services\Payments\OnlinePaymentService;
use Illuminate\Console\Command;
use Throwable;

/** Paiements en ligne en attente depuis plus de 30 minutes : statut re-vérifié (PAY-03). */
final class CheckPendingPayments extends Command
{
    protected $signature = 'immora:payments-check';

    protected $description = 'Vérifie auprès de l\'agrégateur les paiements en ligne restés en attente';

    public function handle(OnlinePaymentService $online): int
    {
        $intents = PaymentIntent::query()->where('status', 'pending')
            ->whereBetween('created_at', [now()->subDays(3), now()->subMinutes(30)])->get();

        $confirmed = 0;
        foreach ($intents as $intent) {
            try {
                if ($online->confirm($intent) !== null) {
                    $confirmed++;
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $this->info("{$intents->count()} paiement(s) vérifié(s), {$confirmed} confirmé(s).");

        return self::SUCCESS;
    }
}
