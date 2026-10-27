<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\View\View;

/** État des impayés par ancienneté (CDC chap. 19). */
final class ReportController extends Controller
{
    public function arrears(): View
    {
        $today = now()->startOfDay();
        $rows = Invoice::query()->open()->where('due_date', '<', $today->toDateString())
            ->with(['tenant', 'lease.unit.property'])->orderBy('due_date')->get()
            ->groupBy('tenant_id')
            ->map(function ($invoices) use ($today): array {
                $buckets = [0, 0, 0, 0];
                foreach ($invoices as $invoice) {
                    $days = (int) $invoice->due_date->diffInDays($today);
                    $index = $days <= 30 ? 0 : ($days <= 60 ? 1 : ($days <= 90 ? 2 : 3));
                    $buckets[$index] += $invoice->balance();
                }
                $first = $invoices->first();

                return [
                    'tenant' => $first->tenant,
                    'unit' => $first->lease->unit,
                    'oldest' => $first->due_date,
                    'count' => $invoices->count(),
                    'buckets' => $buckets,
                    'total' => array_sum($buckets),
                ];
            })
            ->sortByDesc('total')->values();

        return view('reports.arrears', [
            'rows' => $rows,
            'totals' => collect(range(0, 3))->map(fn (int $i) => $rows->sum(fn ($row) => $row['buckets'][$i]))->all(),
            'grandTotal' => $rows->sum('total'),
        ]);
    }
}
