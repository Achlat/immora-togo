<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Services\SequenceService;
use App\Support\CurrentAgency;
use App\Support\Money;
use App\Support\Phone;
use App\Support\PortalAccess;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Locataires (CDC chap. 12). */
final class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $tenants = Tenant::query()
            ->with(['leases' => fn ($q) => $q->whereIn('status', ['active', 'notice'])->with('unit')])
            ->addSelect(['balance_due' => DB::table('invoices')
                ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0)')
                ->whereColumn('invoices.tenant_id', 'tenants.id')->whereNotIn('status', ['paid', 'cancelled'])])
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) => $w->where('last_name', 'like', "%{$term}%")
                ->orWhere('first_name', 'like', "%{$term}%")->orWhere('company_name', 'like', "%{$term}%")
                ->orWhere('phone_e164', 'like', '%'.preg_replace('/\D/', '', $term).'%')))
            ->when($request->query('filter') === 'arrears', fn ($q) => $q->whereHas('invoices', fn ($i) => $i->open()->where('due_date', '<', now()->toDateString())))
            ->orderBy('last_name')->paginate(25)->withQueryString();

        return view('tenants.index', ['tenants' => $tenants]);
    }

    public function create(): View
    {
        return view('tenants.form', ['tenant' => new Tenant(['kind' => 'person'])]);
    }

    public function store(Request $request, SequenceService $sequences, CurrentAgency $current): RedirectResponse
    {
        $tenant = Tenant::create($this->validated($request) + ['reference' => $sequences->reference($current->id(), 'tenant')]);
        AuditLog::record('tenant.created', $tenant);

        return redirect()->route($request->boolean('then_lease') ? 'leases.create' : 'tenants.show', $request->boolean('then_lease') ? ['tenant' => $tenant->id] : $tenant)
            ->with('success', 'Locataire enregistré.');
    }

    public function show(Tenant $tenant): View
    {
        $tenant->load([
            'leases' => fn ($q) => $q->with('unit.property')->latest('start_date'),
            'invoices' => fn ($q) => $q->latest('due_date')->limit(24),
            'payments' => fn ($q) => $q->latest('paid_at')->limit(12),
        ]);

        return view('tenants.show', [
            'tenant' => $tenant,
            'balance' => $tenant->balanceDue(),
            'credit' => $tenant->availableCredit(),
        ]);
    }

    public function edit(Tenant $tenant): View
    {
        return view('tenants.form', ['tenant' => $tenant]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update($this->validated($request));
        AuditLog::record('tenant.updated', $tenant);

        return redirect()->route('tenants.show', $tenant)->with('success', 'Fiche locataire mise à jour.');
    }

    public function invitePortal(Tenant $tenant, PortalAccess $access, CurrentAgency $current): RedirectResponse
    {
        try {
            $user = $access->grant($current->get(), 'tenant', $tenant->whatsapp_e164 ?: $tenant->phone_e164,
                (string) $tenant->first_name, (string) ($tenant->last_name ?? $tenant->company_name), $tenant->email);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }
        $tenant->update(['user_id' => $user->id]);

        return back()->with('success', 'Accès envoyé par SMS. Le locataire peut payer et télécharger ses quittances en ligne.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $country = app(CurrentAgency::class)->get()->country_code;
        $request->merge([
            'phone_e164' => Phone::normalize($request->input('phone'), $country),
            'whatsapp_e164' => Phone::normalize($request->input('whatsapp'), $country),
            'guarantor_phone' => Phone::normalize($request->input('guarantor_phone_raw'), $country),
            'monthly_income_amount' => $request->filled('monthly_income_amount') ? Money::parse($request->input('monthly_income_amount')) : null,
        ]);

        return $request->validate([
            'kind' => ['required', Rule::in(['person', 'company'])],
            'first_name' => ['nullable', 'required_if:kind,person', 'string', 'max:80'],
            'last_name' => ['nullable', 'required_if:kind,person', 'string', 'max:80'],
            'company_name' => ['nullable', 'required_if:kind,company', 'string', 'max:150'],
            'phone_e164' => ['required', 'string', 'max:20'],
            'whatsapp_e164' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:190'],
            'id_document' => ['nullable', 'string', 'max:80'],
            'profession' => ['nullable', 'string', 'max:120'],
            'employer' => ['nullable', 'string', 'max:150'],
            'monthly_income_amount' => ['nullable', 'integer', 'min:0'],
            'emergency_contact' => ['nullable', 'string', 'max:190'],
            'guarantor_name' => ['nullable', 'string', 'max:150'],
            'guarantor_phone' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], ['phone_e164.required' => 'Numéro de téléphone obligatoire (format 07 01 02 03 04) : il sert aux quittances et relances.']);
    }
}
