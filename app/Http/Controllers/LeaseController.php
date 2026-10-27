<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Lease;
use App\Models\Tenant;
use App\Models\Unit;
use App\Services\Billing\LeaseBillingService;
use App\Services\SequenceService;
use App\Support\CurrentAgency;
use App\Support\Money;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Baux : création, activation, préavis, résiliation (LEASE-01 à LEASE-07). */
final class LeaseController extends Controller
{
    public function __construct(private readonly LeaseBillingService $billing) {}

    public function index(Request $request): View
    {
        $status = $request->query('status', 'active');
        $leases = Lease::query()->with(['unit.property', 'tenant'])
            ->when($status !== 'all', fn ($q) => $q->whereIn('status', $status === 'active' ? ['active', 'notice'] : [$status]))
            ->latest('start_date')->paginate(25)->withQueryString();

        return view('leases.index', ['leases' => $leases, 'status' => $status]);
    }

    public function create(Request $request, CurrentAgency $current): View
    {
        $units = Unit::query()->with('property')->whereIn('status', ['vacant', 'reserved'])->orderBy('reference')->get();
        $selected = $units->firstWhere('id', (int) $request->query('unit'));
        $agency = $current->get();

        return view('leases.create', [
            'units' => $units,
            'tenants' => Tenant::query()->orderBy('last_name')->get(),
            'selectedUnit' => $selected,
            'selectedTenant' => (int) $request->query('tenant'),
            'defaults' => [
                'due_day' => $agency->setting('due_day', 5),
                'grace_days' => $agency->setting('grace_days', 5),
                'penalty_rate' => $agency->setting('penalty_rate', 10),
            ],
        ]);
    }

    public function store(Request $request, SequenceService $sequences, CurrentAgency $current): RedirectResponse
    {
        foreach (['rent_amount', 'charges_amount', 'deposit_amount', 'advance_amount', 'agency_fee_amount'] as $field) {
            $request->merge([$field => Money::parse($request->input($field))]);
        }
        $agencyId = $current->id();
        $data = $request->validate([
            'unit_id' => ['required', Rule::exists('units', 'id')->where('agency_id', $agencyId)],
            'tenant_id' => ['required', Rule::exists('tenants', 'id')->where('agency_id', $agencyId)],
            'type' => ['required', Rule::in(array_keys(Lease::TYPES))],
            'start_date' => ['required', 'date'],
            'duration_months' => ['required', 'integer', 'between:1,120'],
            'rent_amount' => ['required', 'integer', 'min:1000'],
            'charges_amount' => ['required', 'integer', 'min:0'],
            'vat_rate' => ['required', 'numeric', 'between:0,30'],
            'due_day' => ['required', 'integer', 'between:1,28'],
            'grace_days' => ['required', 'integer', 'between:0,30'],
            'penalty_rate' => ['required', 'numeric', 'between:0,50'],
            'deposit_amount' => ['required', 'integer', 'min:0'],
            'advance_amount' => ['required', 'integer', 'min:0'],
            'agency_fee_amount' => ['required', 'integer', 'min:0'],
            'special_terms' => ['nullable', 'string', 'max:4000'],
        ]);

        $start = Carbon::parse($data['start_date']);
        $lease = Lease::create($data + [
            'reference' => $sequences->next($agencyId, 'lease', (int) $start->format('Y')),
            'end_date' => $start->copy()->addMonths($data['duration_months'])->subDay()->toDateString(),
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);
        AuditLog::record('lease.created', $lease);

        return redirect()->route('leases.show', $lease)->with('success', 'Bail enregistré en brouillon. Vérifiez la facture d\'entrée puis activez-le.');
    }

    public function show(Lease $lease): View
    {
        $lease->load(['unit.property.owner', 'tenant', 'invoices' => fn ($q) => $q->orderBy('period_start')]);

        return view('leases.show', ['lease' => $lease, 'preview' => $this->entryPreview($lease)]);
    }

    public function activate(Lease $lease): RedirectResponse
    {
        try {
            $this->billing->activate($lease);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Bail activé : facture d\'entrée et échéancier générés, lot marqué occupé.');
    }

    public function notice(Request $request, Lease $lease): RedirectResponse
    {
        $data = $request->validate(['notice_date' => ['required', 'date'], 'end_date' => ['required', 'date', 'after_or_equal:notice_date']]);
        abort_unless($lease->status === 'active', 422);
        $lease->update(['status' => 'notice', 'notice_date' => $data['notice_date'], 'end_date' => $data['end_date']]);
        AuditLog::record('lease.notice', $lease, $data);

        return back()->with('success', 'Préavis enregistré. Sortie prévue le '.Carbon::parse($data['end_date'])->format('d/m/Y').'.');
    }

    public function terminate(Request $request, Lease $lease): RedirectResponse
    {
        $data = $request->validate(['end_date' => ['required', 'date'], 'reason' => ['nullable', 'string', 'max:255']]);
        abort_unless(in_array($lease->status, ['active', 'notice'], true), 422);
        $this->billing->terminate($lease, Carbon::parse($data['end_date']), $data['reason'] ?? null);

        return back()->with('success', 'Bail résilié. Le lot redevient disponible.');
    }

    /** @return list<array{label:string, amount:int}> */
    private function entryPreview(Lease $lease): array
    {
        $start = $lease->start_date;
        $days = $start->daysInMonth - $start->day + 1;
        $full = $start->day === 1;
        $rent = $full ? $lease->rent_amount : Money::prorata($lease->rent_amount, $days, $start->daysInMonth);
        $charges = $full ? $lease->charges_amount : Money::prorata($lease->charges_amount, $days, $start->daysInMonth);

        return array_values(array_filter([
            ['label' => $full ? 'Loyer du premier mois' : "Loyer au prorata ({$days} j / {$start->daysInMonth})", 'amount' => $rent],
            ['label' => 'Charges', 'amount' => $charges],
            ['label' => 'TVA', 'amount' => Money::percent($rent + $charges, $lease->vat_rate)],
            ['label' => 'Caution', 'amount' => $lease->deposit_amount],
            ['label' => 'Avance sur loyers', 'amount' => $lease->advance_amount],
            ['label' => "Frais d'agence", 'amount' => $lease->agency_fee_amount],
        ], fn (array $line) => $line['amount'] > 0));
    }
}
