@extends('pdf.layout')
@section('title', $isReceipt ? 'Quittance '.$invoice->receipt_number : 'Avis d\'échéance '.$invoice->number)
@section('doc-type', $isReceipt ? 'Quittance de loyer' : 'Avis d\'échéance')
@section('doc-number', $isReceipt ? $invoice->receipt_number : $invoice->number)

@php($fmt = fn ($n) => number_format((int) $n, 0, ',', ' ').' FCFA')

@section('content')
<table class="grid"><tr>
    <td style="width: 55%">
        <div class="label">Locataire</div>
        <div class="strong">{{ $invoice->tenant->displayName() }}</div>
        <div class="muted">{{ \App\Support\Phone::display($invoice->tenant->phone_e164) }}</div>
    </td>
    <td>
        <div class="label">Logement</div>
        <div class="strong">{{ $invoice->lease->unit->property->name }} — {{ $invoice->lease->unit->label }}</div>
        <div class="muted">{{ $invoice->lease->unit->property->district }}, {{ $invoice->lease->unit->property->city }}</div>
    </td>
</tr></table>

<table class="grid" style="margin-top: 16px"><tr>
    <td style="width: 55%"><div class="label">Période</div><div class="strong">{{ $invoice->periodLabel() }}</div>
        <div class="muted">du {{ $invoice->period_start->format('d/m/Y') }} au {{ $invoice->period_end->format('d/m/Y') }}</div></td>
    <td><div class="label">{{ $isReceipt ? 'Réglée le' : 'À régler avant le' }}</div>
        <div class="strong">{{ $isReceipt ? $invoice->paid_at?->format('d/m/Y') : $invoice->due_date->format('d/m/Y') }}</div>
        <div class="muted">Bail {{ $invoice->lease->reference }}</div></td>
</tr></table>

<table class="lines">
    <thead><tr><th>Désignation</th><th class="r">Montant</th>@unless ($isReceipt)<th class="r">Déjà réglé</th>@endunless</tr></thead>
    <tbody>
        @foreach ($invoice->lines as $line)
            <tr><td>{{ $line->label }}</td><td class="r">{{ $fmt($line->amount) }}</td>@unless ($isReceipt)<td class="r">{{ $fmt($line->paid_amount) }}</td>@endunless</tr>
        @endforeach
    </tbody>
</table>

<div class="total-box"><table><tr>
    <td class="total-label">{{ $isReceipt ? 'Montant acquitté' : 'Reste à payer' }}</td>
    <td class="total-amount">{{ $fmt($isReceipt ? $invoice->total_amount : $invoice->balance()) }}</td>
</tr></table></div>
<div class="words">Arrêté à la somme de {{ $words }}.</div>

@if ($isReceipt)
    <table class="grid" style="margin-top: 26px"><tr>
        <td style="width: 70%; padding-right: 20px">
            <p>Je soussigné(e), représentant {{ $agency->name }}, mandataire du bailleur, reconnais avoir reçu de
                <strong>{{ $invoice->tenant->displayName() }}</strong> la somme ci-dessus au titre du loyer et des charges de la période indiquée,
                et lui en donne quittance, sous réserve de tous droits.</p>
            <p class="muted" style="font-size: 7.5pt">Cette quittance annule tous les reçus partiels donnés pour la même période.
                @if ($invoice->kind === 'rent')Le dépôt de garantie n'est pas compris dans la présente quittance.@else Le dépôt de garantie ci-dessus est conservé par l'agence et restitué en fin de bail, déduction faite des retenues éventuelles.@endif</p>
            <div class="stamp" style="margin-top: 10px">Payé</div>
        </td>
        <td style="text-align: center">
            <img src="{{ $qr }}" style="width: 110px; height: 110px" alt="">
            <div class="muted" style="font-size: 6.5pt; margin-top: 4px">Scannez pour vérifier<br>l'authenticité · {{ $invoice->verify_code }}</div>
        </td>
    </tr></table>
@else
    <p style="margin-top: 24px">Paiement possible en agence (espèces, chèque), par virement ou par Mobile Money depuis votre espace en ligne.
        Passé un délai de {{ $invoice->lease->grace_days }} jours après l'échéance, une pénalité de {{ (float) $invoice->lease->penalty_rate }} % du reste dû pourra être appliquée.</p>
@endif
@endsection
