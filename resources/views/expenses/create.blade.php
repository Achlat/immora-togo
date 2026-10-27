@extends('layouts.app')
@section('title', 'Nouvelle dépense')

@section('content')
<x-page-header title="Nouvelle dépense" kicker="Finance" :back="route('expenses.index')">
    <x-slot:subtitle>Joignez la facture ou une photo du reçu : elle apparaîtra dans le relevé du propriétaire.</x-slot:subtitle>
</x-page-header>

@php($unitsByProperty = $properties->mapWithKeys(fn ($p) => [$p->id => $p->units->map(fn ($u) => ['id' => $u->id, 'label' => $u->label])->values()]))
<form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data" class="card max-w-3xl p-6 md:p-8"
    x-data="{ property: @js((string) old('property_id', '')), units: @js($unitsByProperty) }">
    @csrf
    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label for="property_id" class="label">Bien concerné</label>
            <select id="property_id" name="property_id" class="input" x-model="property">
                <option value="">Aucun (frais de l'agence)</option>
                @foreach ($properties as $property)
                    <option value="{{ $property->id }}">{{ $property->name }}{{ $property->owner ? ' — '.$property->owner->displayName() : '' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="unit_id" class="label">Lot (facultatif)</label>
            <select id="unit_id" name="unit_id" class="input">
                <option value="">Parties communes / tout le bien</option>
                <template x-for="unit in (units[property] || [])" :key="unit.id"><option :value="unit.id" x-text="unit.label"></option></template>
            </select>
        </div>
        <x-field name="charged_to" label="Imputée à" :options="\App\Models\Expense::CHARGED_TO" value="owner" required />
        <x-field name="category" label="Catégorie" :options="config('immora.expense_categories')" required placeholder="Choisir…" />
        <x-field name="label" label="Libellé" required class="md:col-span-2" placeholder="Remplacement du chauffe-eau — A03" />
        <x-field name="amount" label="Montant" money required />
        <x-field name="spent_on" type="date" label="Date" :value="now()->toDateString()" required />
        <x-field name="supplier" label="Prestataire / fournisseur" />
        <x-field name="receipt" type="file" label="Justificatif (photo ou PDF, 5 Mo max)" accept="image/*,application/pdf" />
    </div>
    <div class="rule-gold my-7"></div>
    <div class="flex justify-end gap-2">
        <a href="{{ route('expenses.index') }}" class="btn btn-ghost">Annuler</a>
        <button class="btn btn-primary">Enregistrer la dépense</button>
    </div>
</form>
@endsection
