@extends('layouts.app')
@section('title', 'Reçu '.$payment->number)

@section('content')
<x-page-header :title="\App\Support\Money::format($payment->amount)" :kicker="($payment->amount < 0 ? 'Contre-écriture ' : 'Reçu ').$payment->number" :back="route('payments.index')">
    <x-slot:subtitle>
        <a href="{{ route('tenants.show', $payment->tenant) }}" class="font-semibold text-ink hover:text-gold-deep">{{ $payment->tenant->displayName() }}</a>
        · {{ $payment->methodLabel() }} · {{ $payment->paid_at->translatedFormat('j F Y \à H\hi') }}
    </x-slot:subtitle>
    <x-slot:actions>
        @if ($payment->isReversed())<x-badge status="reversed" label="Annulé" class="!py-1.5 !px-3" />@endif
        <a href="{{ route('payments.pdf', $payment) }}" target="_blank" class="btn btn-ghost"><x-icon name="download" class="size-4" /> Reçu PDF</a>
    </x-slot:actions>
</x-page-header>

<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
    <div class="space-y-6">
        <div class="card overflow-hidden">
            <div class="border-b border-line px-5 py-4"><h2 class="display text-[19px]">Imputation</h2></div>
            <table class="table">
                <thead><tr><th>Échéance</th><th>Ligne</th><th class="text-right">Montant</th></tr></thead>
                <tbody>
                    @foreach ($payment->allocations as $allocation)
                        <tr>
                            <td><a href="{{ route('invoices.show', $allocation->line->invoice) }}" class="font-semibold hover:text-gold-deep">{{ $allocation->line->invoice->periodLabel() }}</a></td>
                            <td class="text-graphite">{{ $allocation->line->label }}</td>
                            <td class="amount"><x-money :amount="$allocation->amount" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($invoices->where('status', 'paid')->whereNotNull('receipt_number')->isNotEmpty())
            <div class="card p-5">
                <p class="eyebrow">Quittances émises</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($invoices->where('status', 'paid')->whereNotNull('receipt_number') as $invoice)
                        <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="inline-flex items-center gap-2 rounded-[8px] border border-line px-3 py-2 text-[13px] font-semibold hover:border-ink">
                            <x-icon name="download" class="size-3.5 text-gold-deep" /> {{ $invoice->receipt_number }} · {{ $invoice->periodLabel() }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <aside class="space-y-6">
        <div class="card p-5">
            <dl class="space-y-3 text-[13.5px]">
                <div class="flex justify-between"><dt class="text-muted">Saisi par</dt><dd class="font-semibold">{{ $payment->creator?->fullName() ?? 'Paiement en ligne' }}</dd></div>
                @if ($payment->external_ref)<div class="flex justify-between gap-3"><dt class="text-muted">Réf. opérateur</dt><dd class="truncate font-mono text-[12px]">{{ $payment->external_ref }}</dd></div>@endif
                @if ($payment->reference_note)<div class="flex justify-between gap-3"><dt class="text-muted">Note</dt><dd class="text-right">{{ $payment->reference_note }}</dd></div>@endif
                <div class="flex justify-between"><dt class="text-muted">Vérification</dt><dd><a href="{{ route('verify.show', $payment->verify_code) }}" target="_blank" class="link font-mono text-[12px]">{{ $payment->verify_code }}</a></dd></div>
            </dl>
            @if ($payment->reversal_reason)
                <p class="mt-4 rounded-[8px] bg-alert-wash px-3 py-2.5 text-[12.5px] text-alert"><strong>Motif d'annulation :</strong> {{ $payment->reversal_reason }}</p>
            @endif
        </div>

        @can('payments.reverse')
            @if ($payment->status === 'confirmed' && $payment->amount > 0)
                <form method="POST" action="{{ route('payments.reverse', $payment) }}" class="card space-y-4 p-5" data-confirm="Annuler cet encaissement ? Une contre-écriture sera créée et les échéances redeviendront dues.">
                    @csrf
                    <p class="eyebrow !text-alert">Annuler l'encaissement</p>
                    <p class="text-[12.5px] text-muted">Aucune suppression : une contre-écriture numérotée est créée et tracée (RENT-06).</p>
                    <x-field name="reason" type="textarea" label="Motif" required placeholder="Ex. chèque rejeté par la banque le 24/10" />
                    <button class="btn btn-danger w-full">Annuler par contre-écriture</button>
                </form>
            @endif
        @endcan
    </aside>
</div>
@endsection
