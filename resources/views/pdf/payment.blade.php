@extends('pdf.layout')
@section('title', 'Reçu '.$payment->number)
@section('doc-type', $payment->amount < 0 ? 'Contre-écriture' : 'Reçu de paiement')
@section('doc-number', $payment->number)

@php($fmt = fn ($n) => number_format((int) $n, 0, ',', ' ').' FCFA')

@section('content')
<table class="grid"><tr>
    <td style="width: 55%">
        <div class="label">Reçu de</div>
        <div class="strong">{{ $payment->tenant->displayName() }}</div>
        <div class="muted">{{ \App\Support\Phone::display($payment->tenant->phone_e164) }}</div>
    </td>
    <td>
        <div class="label">Date et mode</div>
        <div class="strong">{{ $payment->paid_at->format('d/m/Y à H:i') }}</div>
        <div class="muted">{{ $payment->methodLabel() }}@if ($payment->external_ref) · réf. {{ $payment->external_ref }}@endif</div>
    </td>
</tr></table>

<table class="lines">
    <thead><tr><th>Échéance</th><th>Imputation</th><th class="r">Montant</th></tr></thead>
    <tbody>
        @forelse ($payment->allocations as $allocation)
            <tr><td>{{ $allocation->line->invoice->periodLabel() }}</td><td>{{ $allocation->line->label }}</td><td class="r">{{ $fmt($allocation->amount) }}</td></tr>
        @empty
            <tr><td colspan="3" class="muted">Montant enregistré en avance sur les prochaines échéances.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="total-box"><table><tr>
    <td class="total-label">Montant reçu</td>
    <td class="total-amount">{{ $fmt($payment->amount) }}</td>
</tr></table></div>
<div class="words">Arrêté à la somme de {{ $words }}.</div>

<table class="grid" style="margin-top: 26px"><tr>
    <td style="width: 70%; padding-right: 20px">
        <p>Reçu établi par {{ $payment->creator?->fullName() ?? 'paiement en ligne' }} pour {{ $agency->name }}.</p>
        @if ($payment->status === 'reversed')
            <div class="stamp void">Annulé</div>
            <p class="muted">Motif : {{ $payment->reversal_reason }}</p>
        @else
            <div class="stamp">Reçu</div>
        @endif
    </td>
    <td style="text-align: center">
        <img src="{{ $qr }}" style="width: 110px; height: 110px" alt="">
        <div class="muted" style="font-size: 6.5pt; margin-top: 4px">Vérification · {{ $payment->verify_code }}</div>
    </td>
</tr></table>
@endsection
