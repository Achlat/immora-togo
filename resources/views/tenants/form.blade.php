@extends('layouts.app')
@section('title', $tenant->exists ? 'Modifier le locataire' : 'Nouveau locataire')

@section('content')
<x-page-header :title="$tenant->exists ? $tenant->displayName() : 'Nouveau locataire'" kicker="Locataire"
    :back="$tenant->exists ? route('tenants.show', $tenant) : route('tenants.index')" />

<form method="POST" action="{{ $tenant->exists ? route('tenants.update', $tenant) : route('tenants.store') }}" class="max-w-4xl space-y-6"
    x-data="{ kind: @js(old('kind', $tenant->kind)) }">
    @csrf
    @if ($tenant->exists) @method('PUT') @endif

    <section class="card p-6 md:p-8">
        <h2 class="display text-[20px]">Identité</h2>
        <div class="segment mt-5 max-w-sm">
            <label :aria-selected="kind === 'person'" class="cursor-pointer"><input type="radio" name="kind" value="person" x-model="kind" class="sr-only">Particulier</label>
            <label :aria-selected="kind === 'company'" class="cursor-pointer"><input type="radio" name="kind" value="company" x-model="kind" class="sr-only">Entreprise</label>
        </div>
        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <template x-if="kind === 'person'"><x-field name="first_name" label="Prénom" :value="$tenant->first_name" /></template>
            <template x-if="kind === 'person'"><x-field name="last_name" label="Nom" :value="$tenant->last_name" /></template>
            <template x-if="kind === 'company'"><x-field name="company_name" label="Raison sociale" :value="$tenant->company_name" class="md:col-span-2" /></template>
            <x-field name="phone" type="tel" label="Téléphone" :value="$tenant->phone_e164" required placeholder="+228 90 12 34 56" hint="Reçoit les quittances et les relances." />
            <x-field name="whatsapp" type="tel" label="WhatsApp (si différent)" :value="$tenant->whatsapp_e164" />
            <x-field name="email" type="email" label="E-mail" :value="$tenant->email" />
            <x-field name="id_document" label="Pièce d'identité (CNI, passeport…)" :value="$tenant->id_document" />
        </div>
    </section>

    <section class="card p-6 md:p-8">
        <h2 class="display text-[20px]">Solvabilité & garant</h2>
        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <x-field name="profession" label="Profession" :value="$tenant->profession" />
            <x-field name="employer" label="Employeur" :value="$tenant->employer" />
            <x-field name="monthly_income_amount" label="Revenus mensuels déclarés" money :value="$tenant->monthly_income_amount" hint="Alerte si le loyer dépasse 35 % des revenus." />
            <x-field name="emergency_contact" label="Contact d'urgence" :value="$tenant->emergency_contact" />
            <x-field name="guarantor_name" label="Garant" :value="$tenant->guarantor_name" />
            <x-field name="guarantor_phone_raw" type="tel" label="Téléphone du garant" :value="$tenant->guarantor_phone" />
            <x-field name="notes" type="textarea" label="Notes internes" :value="$tenant->notes" class="md:col-span-2" />
        </div>
    </section>

    <div class="flex justify-end gap-2">
        <a href="{{ $tenant->exists ? route('tenants.show', $tenant) : route('tenants.index') }}" class="btn btn-ghost">Annuler</a>
        @unless ($tenant->exists)
            <button name="then_lease" value="1" class="btn btn-ghost">Enregistrer et créer le bail</button>
        @endunless
        <button class="btn btn-primary">{{ $tenant->exists ? 'Enregistrer' : 'Créer le locataire' }}</button>
    </div>
</form>
@endsection
