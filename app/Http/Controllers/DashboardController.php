<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Lease;
use App\Models\OwnerStatement;
use App\Models\Payment;
use App\Models\Unit;
use App\Support\CurrentAgency;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Tableau de bord (DASH-01 à DASH-05) : « qu'est-ce qui demande mon attention aujourd'hui ? »
 */
final class DashboardController extends Controller
{
    public function __invoke(CurrentAgency $current): View
    {
        $today = now()->startOfDay();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $collected = (int) Payment::query()->whereBetween('paid_at', [$monthStart, $monthEnd])->sum('amount');
        $monthInvoices = Invoice::query()->where('status', '!=', 'cancelled')
            ->whereBetween('period_start', [$monthStart->toDateString(), $monthEnd->toDateString()]);
        $expected = (int) (clone $monthInvoices)->sum('total_amount');
        $expectedPaid = (int) (clone $monthInvoices)->sum('paid_amount');

        $overdue = Invoice::query()->open()->where('due_date', '<', $today->toDateString())->get(['id', 'tenant_id', 'due_date', 'total_amount', 'paid_amount']);
        $buckets = ['0–30 j' => 0, '31–60 j' => 0, '61–90 j' => 0, '+ 90 j' => 0];
        foreach ($overdue as $invoice) {
            $days = (int) $invoice->due_date->diffInDays($today);
            $key = match (true) {
                $days <= 30 => '0–30 j', $days <= 60 => '31–60 j', $days <= 90 => '61–90 j', default => '+ 90 j',
            };
            $buckets[$key] += $invoice->balance();
        }

        $unitsTotal = Unit::query()->whereNotIn('status', ['unavailable'])->count();
        $unitsOccupied = Unit::query()->where('status', 'occupied')->count();

        return view('dashboard.index', [
            'collected' => $collected,
            'expected' => $expected,
            'recoveryRate' => $expected > 0 ? round($expectedPaid / $expected * 100, 1) : null,
            'arrearsTotal' => array_sum($buckets),
            'arrearsTenants' => $overdue->pluck('tenant_id')->unique()->count(),
            'buckets' => $buckets,
            'unitsTotal' => $unitsTotal,
            'unitsOccupied' => $unitsOccupied,
            'occupancy' => $unitsTotal > 0 ? round($unitsOccupied / $unitsTotal * 100, 1) : 0,
            'chart' => $this->chart($today),
            'todo' => [
                'overdue' => $overdue->count(),
                'renewals' => Lease::query()->whereIn('status', ['active', 'notice'])->whereNotNull('end_date')
                    ->whereBetween('end_date', [$today->toDateString(), $today->copy()->addDays(60)->toDateString()])->count(),
                'statements' => OwnerStatement::query()->where('status', 'draft')->count(),
                'drafts' => Lease::query()->where('status', 'draft')->count(),
            ],
            'latestPayments' => Payment::query()->with('tenant')->latest('paid_at')->limit(6)->get(),
            'topArrears' => Invoice::query()->open()->where('due_date', '<', $today->toDateString())
                ->select('tenant_id', DB::raw('SUM(total_amount - paid_amount) as due'), DB::raw('MIN(due_date) as oldest'))
                ->groupBy('tenant_id')->orderByDesc('due')->with('tenant')->limit(5)->get(),
            'greeting' => $this->greeting(),
            'agencyName' => $current->get()->name,
        ]);
    }

    /** @return list<array{label:string, collected:int, expected:int}> */
    private function chart(Carbon $today): array
    {
        $from = $today->copy()->startOfMonth()->subMonths(11);

        $collected = Payment::query()->where('paid_at', '>=', $from)
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, SUM(amount) as total")->groupBy('ym')->pluck('total', 'ym');
        $expected = Invoice::query()->where('status', '!=', 'cancelled')->where('period_start', '>=', $from->toDateString())
            ->selectRaw("DATE_FORMAT(period_start, '%Y-%m') as ym, SUM(total_amount) as total")->groupBy('ym')->pluck('total', 'ym');

        $rows = [];
        for ($month = $from->copy(); $month->lte($today); $month->addMonth()) {
            $key = $month->format('Y-m');
            $rows[] = [
                'label' => ucfirst($month->translatedFormat('M')),
                'collected' => (int) ($collected[$key] ?? 0),
                'expected' => (int) ($expected[$key] ?? 0),
            ];
        }

        return $rows;
    }

    private function greeting(): string
    {
        $hour = (int) now()->format('G');

        return $hour < 12 ? 'Bonjour' : ($hour < 18 ? 'Bon après-midi' : 'Bonsoir');
    }
}
