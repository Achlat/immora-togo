@extends('layouts.app')
@section('title', 'Nouveau bail')

@section('content')
<x-page-header title="Nouveau bail" kicker="Location" :back="route('leases.index')">
    <x-slot:subtitle>Le bail est d'abord enregistré en brouillon : vous vérifiez la facture d'entrée avant de l'activer.</x-slot:subtitle>
</x-page-header>

@php
    $unitsData = $units->mapWithKeys(fn ($u) => [$u->id => ['rent' => $u->base_rent_amount, 'charges' => $u->charges_amount, 'deposit' => $u->deposit_months, 'advance' => $u->advance_months, 'commercial' => $u->usage_type === 'commercial']]);
@endphp

<form method="POST" action="{{ route('leases.store') }}" class="max-w-4xl space-y-6"
    x-data="{
        units: @js($unitsData),
        unit: @js((string) old('unit_id', $selectedUnit?->id ?? '')),
        apply() {
            const u = this.units[this.unit]; if (!u) return;
            const set = (name, v) => { const el = this.$root.querySelector(`[name=${name}]`); el.value = String(v).replace(/\B(?=(\d{3})+(?!\d))/g, ' '); el.dispatchEvent(new Event('input')); };
            set('rent_amount', u.rent); set('charges_amount', u.charges);
            set('deposit_amount', u.rent * u.deposit); set('advance_amount', u.rent * u.advance); set('agency_fee_amount', u.rent);
            this.$root.querySelector('[name=vat_rate]').value = u.commercial ? 18 : 0;
            this.$root.querySelector('[name=type]').value = u.commercial ? 'commercial' : 'habitation';
        }
    }">
    @csrf
    <section class="card p-6 md:p-8">
        <h2 class="display text-[20px]">Parties</h2>
        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <div>
                <label for="unit_id" class="label">Lot <span class="text-gold-deep">*</span></label>
                <select id="unit_id" name="unit_id" class="input" x-model="unit" @change="apply()" required>
                    <option value="">Choisir un lot vacant…</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->reference }} — {{ $unit->property->name }} ({{ $unit->typeLabel() }})</option>
                    @endforeach
                </select>
                @error('unit_id')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="tenant_id" class="label">Locataire <span class="text-gold-deep">*</span></label>
                <select id="tenant_id" name="tenant_id" class="input" required>
                    <option value="">Choisir…</option>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected(old('tenant_id', $selectedTenant) == $tenant->id)>{{ $tenant->displayName() }} · {{ \App\Support\Phone::display($tenant->phone_e164) }}</option>
                    @endforeach
                </select>
                <p class="hint">Absent de la liste ? <a href="{{ route('tenants.create') }}" class="link">Créer le locataire</a></p>
            </div>
            <x-field name="type" label="Type de bail" :options="\App\Models\Lease::TYPES" value="habitation" required />
            <div class="grid grid-cols-2 gap-4">
                <x-field name="start_date" type="date" label="Date d'entrée" :value="now()->toDateString()" required />
                <x-field name="duration_months" type="number" label="Durée (mois)" value="12" required />
            </div>
        </div>
    </section>

    <section class="card p-6 md:p-8">
        <h2 class="display text-[20px]">Loyer & échéance</h2>
        <div class="mt-6 grid gap-5 md:grid-cols-3">
            <x-field name="rent_amount" label="Loyer mensuel HT" money :value="$selectedUnit?->base_rent_amount" required />
            <x-field name="charges_amount" label="Charges mensuelles" money :value="$selectedUnit?->charges_amount ?? 0" required />
            <x-field name="vat_rate" type="number" step="0.01" label="TVA (%)" value="0" required hint="18 % pour un bail commercial." />
            <x-field name="due_day" type="number" label="Jour d'échéance" :value="$defaults['due_day']" required />
            <x-field name="grace_days" type="number" label="Période de grâce (jours)" :value="$defaults['grace_days']" required />
            <x-field name="penalty_rate" type="number" step="0.01" label="Pénalité de retard (%)" :value="$defaults['penalty_rate']" required />
        </div>
    </section>

    <section class="card p-6 md:p-8">
        <h2 class="display text-[20px]">Versements à l'entrée</h2>
        <p class="mt-1 text-[13.5px] text-muted">Pré-remplis depuis le lot : caution et avance en mois de loyer, frais d'agence d'un mois. L'avance paie automatiquement les premières échéances.</p>
        <div class="mt-6 grid gap-5 md:grid-cols-3">
            <x-field name="deposit_amount" label="Caution" money :value="$selectedUnit ? $selectedUnit->base_rent_amount * $selectedUnit->deposit_months : 0" required />
            <x-field name="advance_amount" label="Avance sur loyers" money :value="$selectedUnit ? $selectedUnit->base_rent_amount * $selectedUnit->advance_months : 0" required />
            <x-field name="agency_fee_amount" label="Frais d'agence" money :value="$selectedUnit?->base_rent_amount ?? 0" required />
        </div>
        <x-field name="special_terms" type="textarea" label="Clauses particulières" class="mt-5" />
    </section>

    <div class="flex justify-end gap-2">
        <a href="{{ route('leases.index') }}" class="btn btn-ghost">Annuler</a>
        <button class="btn btn-primary">Enregistrer le brouillon</button>
    </div>
</form>
@endsection
