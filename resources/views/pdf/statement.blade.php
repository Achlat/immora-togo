@extends('pdf.layout')
@section('title', 'Relevé '.$statement->number)
@section('doc-type', 'Relevé de gérance')
@section('doc-number', $statement->number)

@php($fmt = fn ($n) => number_format((int) $n, 0, ',', ' ').' FCFA')
@php($details = $statement->details)

@section('content')
<table class="grid"><tr>
    <td style="width: 55%">
        <div class="label">Propriétaire</div>
        <div class="strong">{{ $statement->owner->displayName() }}</div>
        <div class="muted">{{ $statement->owner->reference }}</div>
    </td>
    <td>
        <div class="label">Période</div>
        <div class="strong">{{ ucfirst($statement->period_start->translatedFormat('F Y')) }}</div>
        <div class="muted">du {{ $statement->period_start->format('d/m/Y') }} au {{ $statement->period_end->format('d/m/Y') }}</div>
    </td>
</tr></table>

<table class="lines">
    <thead><tr><th>Lot</th><th>Locataire</th><th class="r">Loyer</th><th class="r">Charges</th><th class="r">Pénalités</th></tr></thead>
    <tbody>
        @forelse ($details['units'] ?? [] as $row)
            <tr><td>{{ $row['unit'] }}</td><td>{{ $row['tenant'] }}</td><td class="r">{{ $fmt($row['rent']) }}</td><td class="r">{{ $fmt($row['charges']) }}</td><td class="r">{{ $fmt($row['penalty']) }}</td></tr>
        @empty
            <tr><td colspan="5" class="muted">Aucun loyer encaissé sur la période.</td></tr>
        @endforelse
    </tbody>
</table>

@if (! empty($details['expenses']))
    <table class="lines">
        <thead><tr><th>Date</th><th>Dépense</th><th>Catégorie</th><th class="r">Montant</th></tr></thead>
        <tbody>
            @foreach ($details['expenses'] as $expense)
                <tr><td>{{ $expense['date'] }}</td><td>{{ $expense['label'] }}</td><td>{{ $expense['category'] }}</td><td class="r">{{ $fmt($expense['amount']) }}</td></tr>
            @endforeach
        </tbody>
    </table>
@endif

<table class="lines" style="width: 60%; margin-left: 40%">
    <tbody>
        <tr><td>Solde reporté</td><td class="r">{{ $fmt($statement->opening_balance_amount) }}</td></tr>
        <tr><td>Loyers encaissés</td><td class="r">{{ $fmt($statement->collected_amount) }}</td></tr>
        <tr><td>Commission de gérance {{ rtrim(rtrim(number_format((float) ($details['commission_rate'] ?? 0), 2, ',', ''), '0'), ',') }} % HT</td><td class="r">− {{ $fmt($statement->commission_amount) }}</td></tr>
        <tr><td>TVA sur commission</td><td class="r">− {{ $fmt($statement->commission_vat_amount) }}</td></tr>
        <tr><td>Dépenses</td><td class="r">− {{ $fmt($statement->expenses_amount) }}</td></tr>
        <tr><td>Réserve de trésorerie conservée</td><td class="r">− {{ $fmt($statement->reserve_amount) }}</td></tr>
    </tbody>
</table>

<div class="total-box"><table><tr>
    <td class="total-label">Net reversé au propriétaire</td>
    <td class="total-amount">{{ $fmt($statement->payout_amount) }}</td>
</tr></table></div>
<div class="words">Arrêté à la somme de {{ $words }}.</div>
<p class="muted" style="margin-top: 18px; font-size: 7.5pt">La commission est calculée sur les loyers effectivement encaissés. Les dépôts de garantie sont exclus de l'assiette et conservés par l'agence jusqu'à leur restitution.</p>
@endsection
