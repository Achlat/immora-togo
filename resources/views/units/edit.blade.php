@extends('layouts.app')
@section('title', 'Lot '.$unit->label)

@section('content')
<x-page-header :title="'Lot '.$unit->label" :kicker="$unit->property->name.' · '.$unit->reference" :back="route('properties.show', $unit->property)" />

<form method="POST" action="{{ route('units.update', $unit) }}" class="card max-w-3xl p-6 md:p-8">
    @csrf @method('PUT')
    <div class="grid gap-5 md:grid-cols-3">
        <x-field name="label" label="Libellé" :value="$unit->label" required />
        <x-field name="type" label="Type" :options="config('immora.unit_types')" :value="$unit->type" required />
        <x-field name="usage_type" label="Usage" :options="['habitation' => 'Habitation', 'commercial' => 'Commercial', 'mixte' => 'Mixte']" :value="$unit->usage_type" required />
        <x-field name="rooms" type="number" label="Pièces" :value="$unit->rooms" />
        <x-field name="surface_m2" type="number" step="0.5" label="Surface (m²)" :value="$unit->surface_m2" />
        <x-field name="floor" label="Étage" :value="$unit->floor" />
        <x-field name="base_rent_amount" label="Loyer de référence HT" money :value="$unit->base_rent_amount" required />
        <x-field name="charges_amount" label="Charges" money :value="$unit->charges_amount" />
        <x-field name="status" label="Statut" :options="array_intersect_key(\App\Models\Unit::STATUSES, array_flip(['vacant', 'works', 'unavailable', $unit->status]))" :value="$unit->status" required
            hint="« Occupé » et « Réservé » sont calculés à partir des baux." />
        <x-field name="deposit_months" type="number" label="Caution (mois)" :value="$unit->deposit_months" required />
        <x-field name="advance_months" type="number" label="Avance (mois)" :value="$unit->advance_months" required />
        <label class="flex items-center gap-2.5 self-end pb-3 text-[13.5px]">
            <input type="hidden" name="furnished" value="0">
            <input type="checkbox" name="furnished" value="1" class="size-4 accent-ink" @checked(old('furnished', $unit->furnished))> Meublé
        </label>
    </div>
    <div class="rule-gold my-7"></div>
    <div class="flex justify-end gap-2">
        <a href="{{ route('properties.show', $unit->property) }}" class="btn btn-ghost">Annuler</a>
        <button class="btn btn-primary">Enregistrer</button>
    </div>
</form>
@endsection
