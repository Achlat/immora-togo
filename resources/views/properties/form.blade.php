@extends('layouts.app')
@section('title', $property->exists ? 'Modifier le bien' : 'Nouveau bien')

@section('content')
<x-page-header :title="$property->exists ? $property->name : 'Nouveau bien'" kicker="Patrimoine"
    :back="$property->exists ? route('properties.show', $property) : route('properties.index')" />

<form method="POST" action="{{ $property->exists ? route('properties.update', $property) : route('properties.store') }}" class="card max-w-3xl p-6 md:p-8">
    @csrf
    @if ($property->exists) @method('PUT') @endif

    <div class="grid gap-5 md:grid-cols-2">
        <x-field name="name" label="Nom d'usage" :value="$property->name" required placeholder="Résidence Les Palmiers" class="md:col-span-2" />
        <x-field name="type" label="Type de bien" :options="config('immora.property_types')" :value="$property->type" required placeholder="Choisir…" />
        <x-field name="owner_id" label="Propriétaire" :options="$owners" :value="$property->owner_id" placeholder="Patrimoine propre de l'agence"
            hint="Le propriétaire reçoit les loyers de ses lots dans son relevé de gérance." />
        <x-field name="city" label="Ville" :value="$property->city" required />
        <x-field name="district" label="Commune / quartier" :value="$property->district" placeholder="Cocody Riviera 3" />
        <x-field name="landmark" label="Repère" :value="$property->landmark" placeholder="Derrière la pharmacie…" class="md:col-span-2"
            hint="Le repère que vous donneriez à un livreur : il facilite les visites." />
        <x-field name="land_title" label="Titre foncier / ACD" :value="$property->land_title" />
        <x-field name="year_built" type="number" label="Année de construction" :value="$property->year_built" />
        <x-field name="notes" type="textarea" label="Notes internes" :value="$property->notes" class="md:col-span-2" />
    </div>

    <div class="rule-gold my-7"></div>
    <div class="flex justify-end gap-2">
        <a href="{{ $property->exists ? route('properties.show', $property) : route('properties.index') }}" class="btn btn-ghost">Annuler</a>
        <button class="btn btn-primary">{{ $property->exists ? 'Enregistrer' : 'Créer le bien' }}</button>
    </div>
</form>
@endsection
