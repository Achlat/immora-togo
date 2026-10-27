@extends('layouts.app')
@section('title', 'Relevé '.$statement->number)

@section('content')
<x-page-header :title="$statement->owner->displayName()" :kicker="'Relevé '.$statement->number.' · '.ucfirst($statement->period_start->translatedFormat('F Y'))" :back="route('statements.index', ['month' => $statement->period_start->format('Y-m')])">
    <x-slot:actions>
        <x-badge :status="$statement->status" :label="$statement->statusLabel()" class="!py-1.5 !px-3" />
        <a href="{{ route('statements.pdf', $statement) }}" target="_blank" class="btn btn-ghost"><x-icon name="download" class="size-4" /> Relevé PDF</a>
    </x-slot:actions>
</x-page-header>

@php($details = $statement->details)
<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
    <div class="space-y-6">
        <div class="card overflow-hidden">
            <div class="border-b border-line px-5 py-4"><h2 class="display text-[19px]">Loyers encaissés par lot</h2></div>
            <table class="table">
                <thead><tr><th>Lot</th><th>Locataire</th><th class="text-right">Loyer</th><th class="text-right">Charges</th><th class="text-right">Pénalités</th></tr></thead>
                <tbody>
                    @forelse ($details['units'] ?? [] as $row)
                        <tr>
                            <td class="font-semibold">{{ $row['unit'] }}</td>
                            <td class="text-graphite">{{ $row['tenant'] }}</td>
                            <td class="amount"><x-money :amount="$row['rent']" /></td>
                            <td class="amount"><x-money :amount="$row['charges']" /></td>
                            <td class="amount"><x-money :amount="$row['penalty']" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty icon="receipt" title="Aucun encaissement sur la période" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card overflow-hidden">
            <div class="border-b border-line px-5 py-4"><h2 class="display text-[19px]">Dépenses imputées</h2></div>
            <table class="table">
                <tbody>
                    @forelse ($details['expenses'] ?? [] as $expense)
                        <tr><td class="text-graphite">{{ $expense['date'] }}</td><td>{{ $expense['label'] }}</td><td class="amount"><x-money :amount="$expense['amount']" /></td></tr>
                    @empty
                        <tr><td class="text-center text-[13px] text-muted">Aucune dépense ce mois-ci.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <aside class="space-y-6">
        <div class="card overflow-hidden">
            <div class="bg-ink px-5 py-5 text-white">
                <p class="eyebrow !text-gold-light/90">Net à reverser</p>
                <p class="display num mt-2 text-[34px] leading-none">{{ number_format($statement->payout_amount, 0, ',', ' ') }} <span class="text-[14px] text-gold-light">FCFA</span></p>
            </div>
            <dl class="space-y-2.5 p-5 text-[13.5px]">
                <div class="flex justify-between"><dt class="text-muted">Solde reporté</dt><dd><x-money :amount="$statement->opening_balance_amount" /></dd></div>
                <div class="flex justify-between"><dt class="text-muted">Loyers encaissés</dt><dd><x-money :amount="$statement->collected_amount" class="font-semibold" /></dd></div>
                <div class="flex justify-between"><dt class="text-muted">Commission {{ rtrim(rtrim(number_format((float) ($details['commission_rate'] ?? 0), 2, ',', ''), '0'), ',') }} %</dt><dd>− <x-money :amount="$statement->commission_amount" /></dd></div>
                <div class="flex justify-between"><dt class="text-muted">TVA sur commission</dt><dd>− <x-money :amount="$statement->commission_vat_amount" /></dd></div>
                <div class="flex justify-between"><dt class="text-muted">Dépenses</dt><dd>− <x-money :amount="$statement->expenses_amount" /></dd></div>
                <div class="flex justify-between"><dt class="text-muted">Réserve conservée</dt><dd>− <x-money :amount="$statement->reserve_amount" /></dd></div>
            </dl>
        </div>

        @can('payouts.validate')
            @if ($statement->status === 'draft')
                <form method="POST" action="{{ route('statements.validate', $statement) }}" class="card p-5">
                    @csrf
                    <p class="text-[13px] text-muted">Généré par {{ $statement->creator?->fullName() ?? '—' }}. Au-delà de {{ number_format((int) $currentAgency->setting('payout_dual_control_amount', 1000000), 0, ',', ' ') }} FCFA, une autre personne doit valider.</p>
                    <button class="btn btn-primary mt-4 w-full">Valider le relevé</button>
                </form>
            @elseif ($statement->status === 'validated')
                <form method="POST" action="{{ route('statements.payout', $statement) }}" class="card space-y-4 p-5">
                    @csrf
                    <p class="eyebrow">Enregistrer le reversement</p>
                    <x-field name="method" label="Mode" :options="['transfer' => 'Virement', 'mobile_money' => 'Mobile Money', 'cash' => 'Espèces', 'cheque' => 'Chèque']" :value="$statement->owner->payout_method" required />
                    <x-field name="reference_note" label="Référence (n° d'ordre, de chèque…)" />
                    <button class="btn btn-gold w-full">Confirmer le reversement de {{ \App\Support\Money::format($statement->payout_amount) }}</button>
                </form>
            @else
                <div class="card p-5 text-[13.5px]"><p class="font-semibold text-ok">Reversé le {{ $statement->payout?->paid_at->format('d/m/Y') }}</p><p class="text-muted">{{ $statement->payout?->reference_note }}</p></div>
            @endif
        @endcan
    </aside>
</div>
@endsection
