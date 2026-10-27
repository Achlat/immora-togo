@extends('layouts.portal')
@section('title', 'Mon logement')

@section('content')
<p class="eyebrow !text-gold-deep">Mon logement</p>
<h1 class="display mt-1 text-[30px] leading-tight">Bonjour {{ $tenant->greetingName() }}</h1>
@foreach ($tenant->leases as $lease)
    <p class="mt-1 text-[14px] text-muted">{{ $lease->unit->property->name }} · {{ $lease->unit->label }}@if ($lease->unit->property->district), {{ $lease->unit->property->district }}@endif</p>
@endforeach

<section class="mt-6 overflow-hidden rounded-[16px] bg-ink text-white">
    <div class="p-6">
        <p class="eyebrow !text-gold-light/90">{{ $due > 0 ? 'Montant à régler' : 'Votre situation' }}</p>
        @if ($due > 0)
            <p class="display num mt-2 text-[40px] leading-none">{{ number_format($due, 0, ',', ' ') }} <span class="text-[16px] text-gold-light">FCFA</span></p>
            @if ($openInvoices->first())<p class="mt-2 text-[13px] text-[#a9a59b]">Prochaine échéance : {{ $openInvoices->first()->due_date->translatedFormat('j F Y') }}</p>@endif
            <form method="POST" action="{{ route('portal.pay') }}" class="mt-6">
                @csrf
                <button class="btn btn-gold h-12 w-full text-[15px]">Payer maintenant · Mobile Money</button>
            </form>
            <p class="mt-3 text-center text-[11.5px] text-[#8d897f]">Orange Money · MTN MoMo · Moov Money · Wave · Carte bancaire</p>
        @else
            <p class="display mt-2 text-[30px] leading-tight">Vous êtes à jour.</p>
            <p class="mt-2 text-[13px] text-[#a9a59b]">Merci ! @if ($credit > 0)Avance disponible : {{ number_format($credit, 0, ',', ' ') }} FCFA.@endif</p>
        @endif
    </div>
</section>

@if ($openInvoices->isNotEmpty())
    <section class="card mt-6 overflow-hidden">
        <div class="border-b border-line px-5 py-4"><h2 class="display text-[19px]">Échéances à régler</h2></div>
        <ul class="divide-y divide-line">
            @foreach ($openInvoices as $invoice)
                <li class="flex items-center justify-between gap-3 px-5 py-4">
                    <div><p class="font-semibold">{{ $invoice->periodLabel() }}</p><p class="text-[12px] text-muted">Avant le {{ $invoice->due_date->format('d/m/Y') }}</p></div>
                    <div class="text-right"><x-money :amount="$invoice->balance()" class="font-bold" /><br><x-badge :status="$invoice->status" :label="$invoice->statusLabel()" class="mt-1" /></div>
                </li>
            @endforeach
        </ul>
    </section>
@endif

<section class="card mt-6 overflow-hidden">
    <div class="border-b border-line px-5 py-4"><h2 class="display text-[19px]">Mes quittances</h2></div>
    <ul class="divide-y divide-line">
        @forelse ($receipts as $invoice)
            <li>
                <a href="{{ route('portal.invoice.pdf', $invoice) }}" target="_blank" class="flex items-center justify-between gap-3 px-5 py-3.5 hover:bg-paper">
                    <span><span class="font-semibold">{{ $invoice->periodLabel() }}</span><span class="block font-mono text-[11.5px] text-muted">{{ $invoice->receipt_number }}</span></span>
                    <span class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-gold-deep"><x-icon name="download" class="size-4" /> PDF</span>
                </a>
            </li>
        @empty
            <li class="px-5 py-6 text-center text-[13px] text-muted">Vos quittances apparaîtront ici après chaque paiement.</li>
        @endforelse
    </ul>
</section>

<section class="card mt-6 overflow-hidden">
    <div class="border-b border-line px-5 py-4"><h2 class="display text-[19px]">Historique des paiements</h2></div>
    <ul class="divide-y divide-line">
        @forelse ($payments as $payment)
            <li>
                <a href="{{ route('portal.payment.pdf', $payment) }}" target="_blank" class="flex items-center justify-between px-5 py-3.5 hover:bg-paper">
                    <span><span class="font-semibold">{{ $payment->paid_at->format('d/m/Y') }}</span><span class="block text-[12px] text-muted">{{ $payment->methodLabel() }}</span></span>
                    <x-money :amount="$payment->amount" class="font-semibold" />
                </a>
            </li>
        @empty
            <li class="px-5 py-6 text-center text-[13px] text-muted">Aucun paiement pour le moment.</li>
        @endforelse
    </ul>
</section>
@endsection
