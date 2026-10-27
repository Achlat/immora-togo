@extends('layouts.app')
@section('title', 'Encaisser')

@section('content')
<x-page-header title="Encaisser un paiement" kicker="Finance" :back="route('payments.index')">
    <x-slot:subtitle>Le paiement est imputé automatiquement sur les échéances les plus anciennes ; la quittance part par SMS.</x-slot:subtitle>
</x-page-header>

@if (! $tenant)
    {{-- Étape 1 : choisir le locataire --}}
    <div class="card max-w-2xl p-6" x-data="{ q: '' }">
        <label for="tenant-filter" class="label">Locataire</label>
        <input id="tenant-filter" x-model="q" class="input" placeholder="Tapez un nom ou un numéro…" autofocus>
        <ul class="mt-4 max-h-[420px] divide-y divide-line overflow-y-auto rounded-[8px] border border-line">
            @foreach ($tenants as $t)
                <li x-show="!q.trim() || @js(mb_strtolower($t->displayName().' '.$t->phone_e164)).includes(q.trim().toLowerCase())">
                    <a href="{{ route('payments.create', ['tenant' => $t->id]) }}" class="flex items-center justify-between px-4 py-3 hover:bg-paper">
                        <span class="font-semibold">{{ $t->displayName() }}</span>
                        <span class="num text-[12.5px] text-muted">{{ \App\Support\Phone::display($t->phone_e164) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@else
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
        <form method="POST" action="{{ route('payments.store') }}" class="card space-y-5 p-6 md:p-8"
            x-data="{ method: @js(old('method', 'mobile_money')), busy: false }" @submit="busy = true">
            @csrf
            <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
            <div class="flex items-start justify-between">
                <div>
                    <p class="eyebrow">Locataire</p>
                    <p class="display mt-1 text-[22px]">{{ $tenant->displayName() }}</p>
                </div>
                <a href="{{ route('payments.create') }}" class="text-[12.5px] font-semibold text-muted hover:text-ink">Changer</a>
            </div>

            <x-field name="amount" label="Montant reçu" money :value="$balance > 0 ? $balance : null" required class="[&_input]:h-14 [&_input]:text-[22px] [&_input]:font-bold" />

            <div>
                <span class="label">Mode de paiement</span>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach (['mobile_money' => 'Mobile Money', 'cash' => 'Espèces', 'transfer' => 'Virement', 'cheque' => 'Chèque'] as $value => $label)
                        <label class="cursor-pointer rounded-[8px] border px-3 py-2.5 text-center text-[13px] font-semibold transition"
                            :class="method === '{{ $value }}' ? 'border-ink bg-ink text-white' : 'border-line-strong bg-white hover:border-ink'">
                            <input type="radio" name="method" value="{{ $value }}" x-model="method" class="sr-only">{{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div x-show="method === 'mobile_money'" class="grid gap-4 sm:grid-cols-2">
                <x-field name="provider" label="Opérateur" :options="config('immora.mobile_money_providers')" placeholder="Choisir…" />
                <x-field name="external_ref" label="Référence de la transaction" placeholder="Ex. MP251025.1432.A12345" hint="Empêche d'enregistrer deux fois le même paiement." />
            </div>
            <div x-show="method === 'cash'" x-cloak>
                @if ($cashSession)
                    <p class="rounded-[8px] bg-ok-wash px-3.5 py-2.5 text-[13px] font-semibold text-ok">Caisse ouverte depuis {{ $cashSession->opened_at->format('H\hi') }}.</p>
                @else
                    <p class="rounded-[8px] bg-warn-wash px-3.5 py-2.5 text-[13px] font-semibold text-warn">Votre caisse est fermée. <a href="{{ route('cash.index') }}" class="underline">Ouvrir la caisse</a></p>
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="paid_at" type="datetime-local" label="Date du paiement" :value="now()->format('Y-m-d\TH:i')" required />
                <x-field name="reference_note" label="Note (n° de chèque, banque…)" />
            </div>

            <button class="btn btn-gold h-12 w-full text-[15px]" :disabled="busy">
                <span x-show="!busy">Valider l'encaissement et envoyer le reçu</span><span x-show="busy" x-cloak>Enregistrement…</span>
            </button>
        </form>

        <aside class="card h-fit overflow-hidden">
            <div class="flex items-baseline justify-between border-b border-line px-5 py-4">
                <h2 class="display text-[19px]">Dû à ce jour</h2>
                <x-money :amount="$balance" class="display text-[20px] {{ $balance > 0 ? 'text-alert' : 'text-ok' }}" />
            </div>
            <ul class="divide-y divide-line">
                @forelse ($openInvoices as $invoice)
                    <li class="flex items-center justify-between px-5 py-3 text-[13px]">
                        <span><span class="font-semibold">{{ $invoice->periodLabel() }}</span><span class="block text-[12px] text-muted">Échéance {{ $invoice->due_date->format('d/m/Y') }}</span></span>
                        <x-money :amount="$invoice->balance()" class="font-semibold" />
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-[13px] text-muted">Aucune échéance ouverte. Un paiement deviendra une avance sur les prochains loyers.</li>
                @endforelse
            </ul>
        </aside>
    </div>
@endif
@endsection
