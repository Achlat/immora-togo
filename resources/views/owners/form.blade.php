@extends('layouts.app')
@section('title', $owner->exists ? 'Modifier le propriétaire' : 'Nouveau propriétaire')

@section('content')
<x-page-header :title="$owner->exists ? $owner->displayName() : 'Nouveau propriétaire'" kicker="Propriétaire & mandat de gestion"
    :back="$owner->exists ? route('owners.show', $owner) : route('owners.index')" />

<form method="POST" action="{{ $owner->exists ? route('owners.update', $owner) : route('owners.store') }}" class="max-w-4xl space-y-6"
    x-data="{ kind: @js(old('kind', $owner->kind)) }">
    @csrf
    @if ($owner->exists) @method('PUT') @endif

    <section class="card p-6 md:p-8">
        <h2 class="display text-[20px]">Identité</h2>
        <div class="segment mt-5 max-w-sm">
            <label :aria-selected="kind === 'person'" class="cursor-pointer"><input type="radio" name="kind" value="person" x-model="kind" class="sr-only">Particulier</label>
            <label :aria-selected="kind === 'company'" class="cursor-pointer"><input type="radio" name="kind" value="company" x-model="kind" class="sr-only">Société / SCI</label>
        </div>
        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <template x-if="kind === 'person'"><x-field name="first_name" label="Prénom" :value="$owner->first_name" /></template>
            <template x-if="kind === 'person'"><x-field name="last_name" label="Nom" :value="$owner->last_name" /></template>
            <template x-if="kind === 'company'"><x-field name="company_name" label="Raison sociale" :value="$owner->company_name" class="md:col-span-2" /></template>
            <x-field name="phone" type="tel" label="Téléphone" :value="$owner->phone_e164" placeholder="+228 90 12 34 56 ou +33…" hint="Format international accepté pour la diaspora." />
            <x-field name="whatsapp" type="tel" label="WhatsApp" :value="$owner->whatsapp_e164" />
            <x-field name="email" type="email" label="E-mail" :value="$owner->email" />
            <x-field name="country_of_residence" label="Pays de résidence" :value="$owner->country_of_residence ?? 'CI'" maxlength="2" hint="Code à 2 lettres : CI, FR, US…" />
            <x-field name="id_document" label="Pièce d'identité / RCCM" :value="$owner->id_document" />
        </div>
    </section>

    <section class="card p-6 md:p-8">
        <h2 class="display text-[20px]">Mandat de gestion</h2>
        <p class="mt-1 text-[13.5px] text-muted">Ces conditions servent au calcul automatique de chaque relevé de gérance.</p>
        <div class="mt-6 grid gap-5 md:grid-cols-3">
            <x-field name="start_date" type="date" label="Début du mandat" :value="optional($mandate?->start_date)->toDateString() ?? now()->toDateString()" required />
            <x-field name="duration_months" type="number" label="Durée (mois)" :value="$mandate->duration_months ?? 12" required />
            <x-field name="payout_day" type="number" label="Jour de reversement" :value="$mandate->payout_day ?? 10" required />
            <x-field name="commission_rate" type="number" step="0.01" label="Commission (% HT)" :value="$mandate->commission_rate ?? 10" required />
            <x-field name="commission_base" label="Assiette" :options="['rent' => 'Loyers encaissés', 'rent_charges' => 'Loyers + charges encaissés']" :value="$mandate->commission_base ?? 'rent'" required />
            <label class="flex items-center gap-2.5 self-end pb-3 text-[13.5px]">
                <input type="hidden" name="commission_vat" value="0">
                <input type="checkbox" name="commission_vat" value="1" class="size-4 accent-ink" @checked(old('commission_vat', $mandate->commission_vat ?? true))> TVA sur la commission
            </label>
            <x-field name="expense_ceiling_amount" label="Plafond de dépenses sans accord" money :value="$mandate->expense_ceiling_amount ?? 100000" required />
            <x-field name="reserve_amount" label="Réserve de trésorerie conservée" money :value="$mandate->reserve_amount ?? 0" required />
        </div>
    </section>

    <section class="card p-6 md:p-8">
        <h2 class="display text-[20px]">Reversements</h2>
        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <x-field name="payout_method" label="Mode préféré" :options="['transfer' => 'Virement bancaire', 'mobile_money' => 'Mobile Money', 'cash' => 'Espèces au guichet', 'cheque' => 'Chèque']" :value="$owner->payout_method" required />
            <x-field name="payout_details" label="IBAN / RIB / numéro Mobile Money" :value="$owner->payout_details" />
            <x-field name="notes" type="textarea" label="Notes internes" :value="$owner->notes" class="md:col-span-2" />
        </div>
    </section>

    <div class="flex justify-end gap-2">
        <a href="{{ $owner->exists ? route('owners.show', $owner) : route('owners.index') }}" class="btn btn-ghost">Annuler</a>
        <button class="btn btn-primary">{{ $owner->exists ? 'Enregistrer' : 'Créer le propriétaire' }}</button>
    </div>
</form>
@endsection
