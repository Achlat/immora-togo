@extends('layouts.app')
@section('title', 'Caisse')

@section('content')
<x-page-header title="Caisse" kicker="Finance">
    <x-slot:subtitle>Ouverture avec fonds de caisse, clôture avec billetage. Tout écart doit être justifié.</x-slot:subtitle>
</x-page-header>

@if (! $open)
    <form method="POST" action="{{ route('cash.open') }}" class="card max-w-xl p-6">
        @csrf
        <h2 class="display text-[21px]">Ouvrir ma caisse</h2>
        <p class="mt-1 text-[13.5px] text-muted">Indiquez le fonds de caisse présent dans le tiroir.</p>
        <x-field name="opening_amount" label="Fonds de caisse" money value="0" required class="mt-5" />
        <button class="btn btn-primary mt-5">Ouvrir la caisse</button>
    </form>
@else
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_400px]">
        <div class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-stat label="Fonds de caisse" :value="number_format($open->opening_amount, 0, ',', ' ')" unit="FCFA" />
                <x-stat label="Encaissé en espèces" :value="number_format($open->collectedAmount(), 0, ',', ' ')" unit="FCFA" />
                <x-stat label="Attendu dans le tiroir" :value="number_format($open->expectedAmount(), 0, ',', ' ')" unit="FCFA" />
            </div>
            <div class="card overflow-hidden">
                <div class="border-b border-line px-5 py-4"><h2 class="display text-[19px]">Encaissements de la session</h2><p class="text-[12.5px] text-muted">Ouverte le {{ $open->opened_at->format('d/m/Y \à H\hi') }}</p></div>
                <table class="table">
                    <tbody>
                        @forelse ($open->payments as $payment)
                            <tr class="row-link" data-href="{{ route('payments.show', $payment) }}">
                                <td><p class="font-semibold">{{ $payment->tenant->displayName() }}</p><p class="text-[12px] text-muted">{{ $payment->number }} · {{ $payment->paid_at->format('H\hi') }}</p></td>
                                <td class="amount font-semibold"><x-money :amount="$payment->amount" /></td>
                            </tr>
                        @empty
                            <tr><td><x-empty icon="cash" title="Aucun encaissement en espèces" text="Les paiements en espèces saisis s'ajoutent ici." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @can('cash.close')
            <form method="POST" action="{{ route('cash.close', $open) }}" class="card h-fit p-5"
                x-data="{ counts: {}, expected: {{ $open->expectedAmount() }},
                    get total() { return Object.entries(this.counts).reduce((s, [v, q]) => s + Number(v) * (Number(q) || 0), 0) },
                    fmt(n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') } }">
                @csrf
                <h2 class="display text-[20px]">Clôturer la caisse</h2>
                <p class="mt-1 text-[12.5px] text-muted">Comptez les billets et les pièces.</p>
                <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2">
                    @foreach ($denominations as $value)
                        <label class="flex items-center justify-between gap-2 text-[13px]">
                            <span class="num font-semibold">{{ number_format($value, 0, ',', ' ') }}</span>
                            <input type="number" min="0" name="d[{{ $value }}]" x-model="counts[{{ $value }}]" class="input num h-8 w-20 text-right text-[13px]" placeholder="0">
                        </label>
                    @endforeach
                </div>
                <div class="mt-5 space-y-1.5 border-t border-line pt-4 text-[13.5px]">
                    <div class="flex justify-between"><span class="text-muted">Compté</span><span class="num font-bold" x-text="fmt(total) + ' FCFA'"></span></div>
                    <div class="flex justify-between"><span class="text-muted">Attendu</span><span class="num font-semibold">{{ number_format($open->expectedAmount(), 0, ',', ' ') }} FCFA</span></div>
                    <div class="flex justify-between"><span class="text-muted">Écart</span><span class="num font-bold" :class="total - expected === 0 ? 'text-ok' : 'text-alert'" x-text="fmt(total - expected) + ' FCFA'"></span></div>
                </div>
                <div x-show="total !== expected" x-cloak class="mt-4">
                    <x-field name="gap_reason" type="textarea" label="Justification de l'écart" />
                </div>
                <button class="btn btn-primary mt-5 w-full">Clôturer</button>
            </form>
        @endcan
    </div>
@endif

<div class="card mt-8 overflow-hidden">
    <div class="border-b border-line px-5 py-4"><h2 class="display text-[19px]">Clôtures récentes</h2></div>
    <table class="table">
        <thead><tr><th>Caissier</th><th>Ouverture</th><th>Clôture</th><th class="text-right">Attendu</th><th class="text-right">Compté</th><th class="text-right">Écart</th></tr></thead>
        <tbody>
            @forelse ($history as $session)
                @php($gap = $session->counted_amount - $session->expected_amount)
                <tr>
                    <td class="font-semibold">{{ $session->user->fullName() }}</td>
                    <td class="text-graphite">{{ $session->opened_at->format('d/m H\hi') }}</td>
                    <td class="text-graphite">{{ $session->closed_at->format('d/m H\hi') }}</td>
                    <td class="amount"><x-money :amount="$session->expected_amount" /></td>
                    <td class="amount"><x-money :amount="$session->counted_amount" /></td>
                    <td class="amount font-semibold {{ $gap === 0 ? 'text-ok' : 'text-alert' }}" title="{{ $session->gap_reason }}"><x-money :amount="$gap" /></td>
                </tr>
            @empty
                <tr><td colspan="6"><x-empty icon="vault" title="Aucune clôture" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
