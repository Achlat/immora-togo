<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Support\Money;
use Illuminate\Support\Carbon;

/**
 * Pénalité de retard (RG-05) : taux × reste dû, une seule fois par échéance,
 * dès que la date d'exigibilité + la période de grâce est dépassée.
 */
final class PenaltyService
{
    public function apply(Carbon $today): int
    {
        $count = 0;

        Invoice::query()->open()->where('kind', 'rent')->with(['lease', 'lines'])->each(
            function (Invoice $invoice) use ($today, &$count): void {
                if ($this->applyTo($invoice, $today)) {
                    $count++;
                }
            }
        );

        return $count;
    }

    public function applyTo(Invoice $invoice, Carbon $today): bool
    {
        $lease = $invoice->lease;
        $rate = (float) $lease->penalty_rate;
        $deadline = $invoice->due_date->copy()->addDays($lease->grace_days);

        if ($rate <= 0 || ! $today->gt($deadline) || $invoice->lines->contains('type', 'penalty')) {
            return false;
        }

        $penalty = Money::percent($invoice->balance(), $rate);
        if ($penalty <= 0) {
            return false;
        }

        $invoice->lines()->create([
            'type' => 'penalty',
            'label' => 'Pénalité de retard '.rtrim(rtrim((string) $lease->penalty_rate, '0'), '.').' %',
            'amount' => $penalty,
            'priority' => InvoiceLine::priorityFor('penalty'),
        ]);
        $invoice->refreshTotals();
        AuditLog::record('invoice.penalty', $invoice, ['amount' => $penalty]);

        return true;
    }
}
