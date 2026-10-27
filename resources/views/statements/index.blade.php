@extends('layouts.app')
@section('title', 'Relevés de gérance')

@section('content')
<x-page-header title="Relevés de gérance" kicker="Finance">
    <x-slot:subtitle>Un clic génère les relevés de tous les propriétaires du mois. Vérifiez, validez, reversez.</x-slot:subtitle>
    <x-slot:actions>
        <form method="GET" class="flex items-center gap-2"><input type="month" name="month" value="{{ $month->format('Y-m') }}" class="input h-10 w-auto" aria-label="Mois"><button class="btn btn-ghost">Voir</button></form>
        <form method="POST" action="{{ route('statements.generate') }}">@csrf
            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
            <button class="btn btn-gold"><x-icon name="statement" class="size-4" /> Générer les relevés de {{ $month->translatedFormat('F') }}</button>
        </form>
    </x-slot:actions>
</x-page-header>

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <x-stat label="Loyers encaissés" :value="number_format($totals['collected'], 0, ',', ' ')" unit="FCFA" />
    <x-stat label="Commissions agence" :value="number_format($totals['commission'], 0, ',', ' ')" unit="FCFA" />
    <x-stat label="À reverser" :value="number_format($totals['payout'], 0, ',', ' ')" unit="FCFA" />
</div>

<div class="card overflow-hidden">
    <div class="border-b border-line px-5 py-4"><h2 class="display text-[19px]">{{ ucfirst($month->translatedFormat('F Y')) }}</h2></div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Propriétaire</th><th>Relevé</th><th>Statut</th><th class="text-right">Encaissé</th><th class="text-right">Commission + TVA</th><th class="text-right">Dépenses</th><th class="text-right">Net à reverser</th></tr></thead>
            <tbody>
                @forelse ($owners as $owner)
                    @php($s = $statements->get($owner->id))
                    <tr @if ($s) class="row-link" data-href="{{ route('statements.show', $s) }}" @endif>
                        <td class="font-semibold">{{ $owner->displayName() }}</td>
                        <td class="font-mono text-[12px]">{{ $s?->number ?? '—' }}</td>
                        <td>@if ($s)<x-badge :status="$s->status" :label="$s->statusLabel()" />@else<span class="text-[12.5px] text-faint">Non généré</span>@endif</td>
                        <td class="amount">@if ($s)<x-money :amount="$s->collected_amount" />@endif</td>
                        <td class="amount text-graphite">@if ($s)<x-money :amount="$s->commission_amount + $s->commission_vat_amount" />@endif</td>
                        <td class="amount text-graphite">@if ($s)<x-money :amount="$s->expenses_amount" />@endif</td>
                        <td class="amount font-bold">@if ($s)<x-money :amount="$s->payout_amount" />@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty icon="crown" title="Aucun propriétaire sous mandat" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
