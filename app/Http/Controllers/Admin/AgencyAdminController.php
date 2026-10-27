<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Console éditeur : espaces clients et abonnements (SAAS-01 à SAAS-05). */
final class AgencyAdminController extends Controller
{
    public function index(): View
    {
        $agencies = Agency::query()->with('subscription.plan')->withCount('users')->latest()->get();
        $unitCounts = Unit::withoutGlobalScopes()->selectRaw('agency_id, COUNT(*) as total')->groupBy('agency_id')->pluck('total', 'agency_id');

        $mrr = $agencies->sum(function (Agency $agency): int {
            $subscription = $agency->subscription;
            if ($subscription?->status !== 'active') {
                return 0;
            }

            return $subscription->billing_cycle === 'yearly'
                ? intdiv($subscription->plan->yearly_price_amount, 12)
                : $subscription->plan->monthly_price_amount;
        });

        return view('admin.agencies', [
            'agencies' => $agencies,
            'unitCounts' => $unitCounts,
            'plans' => Plan::query()->orderBy('sort')->get(),
            'mrr' => $mrr,
            'trials' => $agencies->filter(fn ($a) => $a->subscription?->status === 'trial')->count(),
        ]);
    }

    public function status(Request $request, Agency $agency): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'suspended'])]]);
        $agency->update(['status' => $data['status']]);
        $agency->subscription?->update(['status' => $data['status'] === 'active' ? 'active' : 'suspended']);
        AuditLog::record("agency.{$data['status']}", $agency);

        return back()->with('success', "Agence {$agency->name} : statut mis à jour.");
    }

    public function extend(Request $request, Agency $agency): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'months' => ['required', 'integer', 'between:1,36'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
        ]);

        $subscription = $agency->subscription;
        $start = $subscription?->current_period_end?->isFuture() ? $subscription->current_period_end : now();
        $agency->subscriptions()->create([
            'plan_id' => $data['plan_id'],
            'billing_cycle' => $data['billing_cycle'],
            'status' => 'active',
            'current_period_end' => $start->copy()->addMonths($data['months'])->toDateString(),
        ]);
        $agency->update(['status' => 'active']);
        AuditLog::record('agency.subscription', $agency, $data);

        return back()->with('success', "Abonnement de {$agency->name} activé jusqu'au ".$start->copy()->addMonths($data['months'])->format('d/m/Y').'.');
    }
}
