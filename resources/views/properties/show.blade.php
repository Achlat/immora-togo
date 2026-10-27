@extends('layouts.app')
@section('title', $property->name)

@section('content')
<x-page-header :title="$property->name" :kicker="$property->typeLabel().' · '.$property->reference" :back="route('properties.index')">
    <x-slot:subtitle>
        <span class="inline-flex items-center gap-1.5"><x-icon name="pin" class="size-3.5" />{{ $property->district ? $property->district.', ' : '' }}{{ $property->city }}</span>
        @if ($property->landmark) · {{ $property->landmark }} @endif
    </x-slot:subtitle>
    @can('properties.manage')
        <x-slot:actions><a href="{{ route('properties.edit', $property) }}" class="btn btn-ghost">Modifier</a></x-slot:actions>
    @endcan
</x-page-header>

@php
    $occupied = $property->units->where('status', 'occupied')->count();
    $monthly = $property->units->sum(fn ($u) => $u->activeLease?->monthlyTotal() ?? 0);
@endphp
<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <x-stat label="Lots" :value="$property->units->count()" :note="$occupied.' occupés'" />
    <x-stat label="Loyers mensuels en cours" :value="number_format($monthly, 0, ',', ' ')" unit="FCFA" />
    <div class="card p-5">
        <p class="eyebrow">Propriétaire</p>
        @if ($property->owner)
            <a href="{{ route('owners.show', $property->owner) }}" class="display mt-3 block text-[20px] hover:text-gold-deep">{{ $property->owner->displayName() }}</a>
            <p class="mt-1 text-[12.5px] text-muted">{{ $property->owner->reference }}</p>
        @else
            <p class="mt-3 text-[14px] text-muted">Patrimoine propre de l'agence</p>
        @endif
    </div>
</div>

<div class="card overflow-hidden">
    <div class="flex items-center justify-between border-b border-line px-5 py-4">
        <h2 class="display text-[20px]">Lots</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Lot</th><th>Type</th><th>Occupant</th><th>Statut</th><th class="text-right">Loyer</th><th></th></tr></thead>
            <tbody>
                @forelse ($property->units as $unit)
                    <tr>
                        <td><p class="font-semibold">{{ $unit->label }}</p><p class="font-mono text-[11.5px] text-muted">{{ $unit->reference }}</p></td>
                        <td class="text-graphite">{{ $unit->typeLabel() }}@if ($unit->rooms) · {{ $unit->rooms }} p.@endif</td>
                        <td>
                            @if ($unit->activeLease)
                                <a href="{{ route('tenants.show', $unit->activeLease->tenant) }}" class="font-medium hover:text-gold-deep">{{ $unit->activeLease->tenant->displayName() }}</a>
                            @else
                                <span class="text-faint">—</span>
                            @endif
                        </td>
                        <td><x-badge :status="$unit->status" :label="$unit->statusLabel()" /></td>
                        <td class="amount"><x-money :amount="$unit->base_rent_amount" /></td>
                        <td class="text-right whitespace-nowrap">
                            @if ($unit->status === 'vacant')
                                @can('leases.manage')<a href="{{ route('leases.create', ['unit' => $unit->id]) }}" class="link text-[12.5px]">Louer</a>@endcan
                            @endif
                            @can('properties.manage')<a href="{{ route('units.edit', $unit) }}" class="ml-3 text-[12.5px] font-semibold text-muted hover:text-ink">Modifier</a>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty icon="key" title="Aucun lot" text="Ajoutez les appartements, chambres ou magasins de ce bien ci-dessous." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('properties.manage')
<form method="POST" action="{{ route('units.store', $property) }}" class="card mt-6 p-6" x-data="{ count: {{ old('count', 1) }} }">
    @csrf
    <h2 class="display text-[20px]">Ajouter des lots</h2>
    <p class="mt-1 text-[13.5px] text-muted">Créez jusqu'à 60 lots identiques d'un coup : « 12 chambres au rez-de-chaussée » se fait en une seule saisie.</p>
    <div class="mt-6 grid gap-5 md:grid-cols-4">
        <x-field name="count" type="number" label="Nombre de lots" value="1" min="1" max="60" x-model="count" required />
        <x-field name="label_prefix" label="Préfixe" value="A" required hint="A01, A02…" />
        <x-field name="start_number" type="number" label="Numéro de départ" value="1" min="1" required />
        <x-field name="type" label="Type" :options="config('immora.unit_types')" value="appartement" required />
        <x-field name="usage_type" label="Usage" :options="['habitation' => 'Habitation', 'commercial' => 'Commercial', 'mixte' => 'Mixte']" value="habitation" required />
        <x-field name="rooms" type="number" label="Pièces" value="2" />
        <x-field name="surface_m2" type="number" label="Surface (m²)" step="0.5" />
        <x-field name="floor" label="Étage" placeholder="RDC, 1er…" />
        <x-field name="base_rent_amount" label="Loyer mensuel HT" money required />
        <x-field name="charges_amount" label="Charges mensuelles" money value="0" />
        <x-field name="deposit_months" type="number" label="Caution (mois)" :value="$currentAgency->setting('default_deposit_months', 2)" required />
        <x-field name="advance_months" type="number" label="Avance (mois)" :value="$currentAgency->setting('default_advance_months', 2)" required />
    </div>
    <div class="mt-6 flex justify-end">
        <button class="btn btn-primary"><x-icon name="plus" class="size-4" /> <span x-text="count > 1 ? 'Créer ' + count + ' lots' : 'Créer le lot'">Créer le lot</span></button>
    </div>
</form>
@endcan
@endsection
