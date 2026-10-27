<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Owner;
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

/** Propriétaires et mandats de gestion (OWN-01 à OWN-05). */
final class OwnerController extends Controller
{
    public function index(Request $request): View
    {
        $owners = Owner::query()->with('mandate')->withCount('properties')
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) => $w->where('last_name', 'like', "%{$term}%")
                ->orWhere('first_name', 'like', "%{$term}%")->orWhere('company_name', 'like', "%{$term}%")))
            ->orderBy('last_name')->orderBy('company_name')->paginate(25)->withQueryString();

        return view('owners.index', ['owners' => $owners]);
    }

    public function create(): View
    {
        return view('owners.form', ['owner' => new Owner(['kind' => 'person', 'payout_method' => 'transfer']), 'mandate' => null]);
    }

    public function store(Request $request, SequenceService $sequences, CurrentAgency $current): RedirectResponse
    {
        [$ownerData, $mandateData] = $this->validated($request);

        $owner = DB::transaction(function () use ($ownerData, $mandateData, $sequences, $current): Owner {
            $owner = Owner::create($ownerData + ['reference' => $sequences->reference($current->id(), 'owner')]);
            $owner->mandate()->create($mandateData + [
                'agency_id' => $current->id(), 'reference' => $sequences->reference($current->id(), 'mandate'), 'status' => 'active',
            ]);

            return $owner;
        });
        AuditLog::record('owner.created', $owner);

        return redirect()->route('owners.show', $owner)->with('success', 'Propriétaire et mandat de gestion enregistrés.');
    }

    public function show(Owner $owner): View
    {
        $owner->load(['mandate', 'properties.units', 'statements' => fn ($q) => $q->latest('period_start')->limit(12)]);

        return view('owners.show', ['owner' => $owner, 'hasPortal' => $owner->user_id !== null]);
    }

    public function edit(Owner $owner): View
    {
        return view('owners.form', ['owner' => $owner, 'mandate' => $owner->mandate]);
    }

    public function update(Request $request, Owner $owner): RedirectResponse
    {
        [$ownerData, $mandateData] = $this->validated($request);
        DB::transaction(function () use ($owner, $ownerData, $mandateData): void {
            $owner->update($ownerData);
            $owner->mandate?->update($mandateData);
        });
        AuditLog::record('owner.updated', $owner);

        return redirect()->route('owners.show', $owner)->with('success', 'Fiche propriétaire mise à jour.');
    }

    public function invitePortal(Owner $owner, PortalAccess $access, CurrentAgency $current): RedirectResponse
    {
        try {
            $user = $access->grant($current->get(), 'owner', $owner->whatsapp_e164 ?: $owner->phone_e164,
                (string) $owner->first_name, (string) ($owner->last_name ?? $owner->company_name), $owner->email);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }
        $owner->update(['user_id' => $user->id]);

        return back()->with('success', 'Accès envoyé par SMS. Le propriétaire se connecte avec son numéro et un code.');
    }

    /** @return array{0: array<string, mixed>, 1: array<string, mixed>} */
    private function validated(Request $request): array
    {
        $country = app(CurrentAgency::class)->get()->country_code;
        $request->merge([
            'phone_e164' => Phone::normalize($request->input('phone'), $country),
            'whatsapp_e164' => Phone::normalize($request->input('whatsapp'), $country),
            'expense_ceiling_amount' => Money::parse($request->input('expense_ceiling_amount')),
            'reserve_amount' => Money::parse($request->input('reserve_amount')),
        ]);

        $data = $request->validate([
            'kind' => ['required', Rule::in(['person', 'company'])],
            'first_name' => ['nullable', 'required_if:kind,person', 'string', 'max:80'],
            'last_name' => ['nullable', 'required_if:kind,person', 'string', 'max:80'],
            'company_name' => ['nullable', 'required_if:kind,company', 'string', 'max:150'],
            'phone_e164' => ['nullable', 'string', 'max:20'],
            'whatsapp_e164' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:190'],
            'country_of_residence' => ['nullable', 'string', 'size:2'],
            'id_document' => ['nullable', 'string', 'max:80'],
            'payout_method' => ['required', Rule::in(['transfer', 'mobile_money', 'cash', 'cheque'])],
            'payout_details' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['required', 'date'],
            'duration_months' => ['required', 'integer', 'between:1,120'],
            'commission_rate' => ['required', 'numeric', 'between:0,50'],
            'commission_vat' => ['nullable', 'boolean'],
            'commission_base' => ['required', Rule::in(['rent', 'rent_charges'])],
            'expense_ceiling_amount' => ['required', 'integer', 'min:0'],
            'reserve_amount' => ['required', 'integer', 'min:0'],
            'payout_day' => ['required', 'integer', 'between:1,28'],
        ]);

        $mandateKeys = ['start_date', 'duration_months', 'commission_rate', 'commission_base', 'expense_ceiling_amount', 'reserve_amount', 'payout_day'];
        $mandate = array_intersect_key($data, array_flip($mandateKeys)) + ['commission_vat' => (bool) ($data['commission_vat'] ?? false)];

        return [array_diff_key($data, array_flip([...$mandateKeys, 'commission_vat'])), $mandate];
    }
}
