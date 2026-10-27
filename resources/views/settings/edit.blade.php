@extends('layouts.app')
@section('title', 'Paramètres')

@section('content')
<x-page-header title="Paramètres de l'agence" kicker="Agence">
    <x-slot:subtitle>Identité, règles de location et sécurité : tout se règle ici, sans développement.</x-slot:subtitle>
</x-page-header>

<form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="max-w-4xl space-y-6">
    @csrf @method('PUT')

    <section class="card p-6 md:p-8">
        <h2 class="display text-[20px]">Identité</h2>
        <p class="mt-1 text-[13.5px] text-muted">Apparaît sur les quittances, relevés et messages envoyés aux clients.</p>
        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <x-field name="name" label="Raison sociale" :value="$agency->name" required class="md:col-span-2" />
            <x-field name="phone_raw" type="tel" label="Téléphone" :value="$agency->phone" />
            <x-field name="email" type="email" label="E-mail" :value="$agency->email" />
            <x-field name="address" label="Adresse" :value="$agency->address" />
            <x-field name="city" label="Ville" :value="$agency->city" />
            <x-field name="rccm" label="RCCM" :value="$agency->rccm" />
            <x-field name="tax_id" label="NCC / NINEA" :value="$agency->tax_id" />
            <x-field name="license_number" label="N° d'agrément" :value="$agency->license_number" />
            <x-field name="vat_rate" type="number" step="0.01" label="Taux de TVA (%)" :value="$agency->vat_rate" required />
            <div class="md:col-span-2">
                <x-field name="logo" type="file" label="Logo (PNG, JPG, 1 Mo max)" accept="image/png,image/jpeg,image/webp" />
                @if ($agency->logo_path)<p class="hint">Un logo est déjà enregistré ; un nouveau fichier le remplace.</p>@endif
            </div>
        </div>
    </section>

    <section class="card p-6 md:p-8">
        <h2 class="display text-[20px]">Règles de location</h2>
        <p class="mt-1 text-[13.5px] text-muted">Valeurs proposées par défaut à chaque nouveau lot et nouveau bail.</p>
        <div class="mt-6 grid gap-5 md:grid-cols-3">
            <x-field name="settings[due_day]" label="Jour d'échéance" type="number" :value="$agency->setting('due_day', 5)" required />
            <x-field name="settings[grace_days]" label="Période de grâce (jours)" type="number" :value="$agency->setting('grace_days', 5)" required />
            <x-field name="settings[penalty_rate]" label="Pénalité de retard (%)" type="number" step="0.01" :value="$agency->setting('penalty_rate', 10)" required />
            <x-field name="settings[default_deposit_months]" label="Caution (mois)" type="number" :value="$agency->setting('default_deposit_months', 2)" required />
            <x-field name="settings[default_advance_months]" label="Avance (mois)" type="number" :value="$agency->setting('default_advance_months', 2)" required />
            <x-field name="settings[online_fee_payer]" label="Frais Mobile Money à la charge de" :options="['agency' => 'L\'agence', 'tenant' => 'Le locataire']" :value="$agency->setting('online_fee_payer', 'agency')" required />
        </div>
        <label class="mt-5 flex items-center gap-2.5 text-[13.5px]">
            <input type="hidden" name="settings[rent_before_penalties]" value="0">
            <input type="checkbox" name="settings[rent_before_penalties]" value="1" class="size-4 accent-ink" @checked($agency->setting('rent_before_penalties', false))>
            Imputer les loyers avant les pénalités (par défaut : pénalités et frais d'abord)
        </label>
    </section>

    <section class="card p-6 md:p-8">
        <h2 class="display text-[20px]">Contrôles & sécurité</h2>
        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <x-field name="settings[payout_dual_control_amount]" label="Double contrôle des reversements au-delà de" money :value="$agency->setting('payout_dual_control_amount', 1000000)" required
                hint="Au-delà, la personne qui valide doit être différente de celle qui a généré le relevé." />
        </div>
        <label class="mt-5 flex items-center gap-2.5 text-[13.5px]">
            <input type="hidden" name="settings[enforce_two_factor]" value="0">
            <input type="checkbox" name="settings[enforce_two_factor]" value="1" class="size-4 accent-ink" @checked($agency->setting('enforce_two_factor', true))>
            Double authentification obligatoire pour les administrateurs et comptables (recommandé)
        </label>
    </section>

    <div class="flex justify-end"><button class="btn btn-primary">Enregistrer les paramètres</button></div>
</form>
@endsection
