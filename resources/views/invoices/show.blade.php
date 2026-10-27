@extends('layouts.app')
@section('title', $invoice->receipt_number ?? $invoice->number)

@section('content')
<x-page-header :title="$invoice->periodLabel()" :kicker="($invoice->receipt_number ? 'Quittance '.$invoice->receipt_number : 'Échéance '.$invoice->number)" :back="route('invoices.index')">
    <x-slot:subtitle>
        <a href="{{ route('tenants.show', $invoice->tenant) }}" class="font-semibold text-ink hover:text-gold-deep">{{ $invoice->tenant->displayName() }}</a>
        · {{ $invoice->lease->unit->property->name }}, lot {{ $invoice->lease->unit->label }} · échéance du {{ $invoice->due_date->format('d/m/Y') }}
    </x-slot:subtitle>
    <x-slot:actions>
        <x-badge :status="$invoice->status" :label="$invoice->statusLabel()" class="!py-1.5 !px-3 !text-[12.5px]" />
        <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="btn btn-ghost"><x-icon name="download" class="size-4" /> {{ $invoice->status === 'paid' ? 'Quittance PDF' : 'Avis d\'échéance PDF' }}</a>
        @if ($invoice->balance() > 0)
            <form method="POST" action="{{ route('invoices.remind', $invoice) }}">@csrf<button class="btn btn-ghost"><x-icon name="send" class="size-4" /> Relancer par SMS</button></form>
            <a href="{{ route('payments.create', ['tenant' => $invoice->tenant_id]) }}" class="btn btn-gold">Encaisser</a>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
    <div class="card overflow-hidden">
        <table class="table">
            <thead><tr><th>Ligne</th><th class="text-right">Montant</th><th class="text-right">Réglé</th><th class="text-right">Reste</th></tr></thead>
            <tbody>
                @foreach ($invoice->lines as $line)
                    <tr>
                        <td>{{ $line->label }}</td>
                        <td class="amount"><x-money :amount="$line->amount" /></td>
                        <td class="amount text-graphite"><x-money :amount="$line->paid_amount" /></td>
                        <td class="amount {{ $line->balance() > 0 ? 'text-alert font-semibold' : 'text-faint' }}"><x-money :amount="$line->balance()" /></td>
                    </tr>
                @endforeach
                <tr class="bg-paper">
                    <td class="display text-[17px]">Total</td>
                    <td class="amount display text-[18px]"><x-money :amount="$invoice->total_amount" /></td>
                    <td class="amount font-semibold"><x-money :amount="$invoice->paid_amount" /></td>
                    <td class="amount font-semibold {{ $invoice->balance() > 0 ? 'text-alert' : '' }}"><x-money :amount="$invoice->balance()" /></td>
                </tr>
            </tbody>
        </table>
    </div>

    <aside class="card p-5">
        <p class="eyebrow">Imputations</p>
        <ul class="mt-4 space-y-3">
            @php($allocations = $invoice->lines->flatMap->allocations->sortBy('allocated_at'))
            @forelse ($allocations as $allocation)
                <li class="flex items-start justify-between gap-3 text-[13px]">
                    <span>
                        @if ($allocation->payment)
                            <a href="{{ route('payments.show', $allocation->payment) }}" class="font-semibold hover:text-gold-deep">{{ $allocation->payment->number }}</a>
                        @else
                            <span class="font-semibold">Avance / avoir</span>
                        @endif
                        <span class="block text-[12px] text-muted">{{ $allocation->allocated_at->format('d/m/Y') }}</span>
                    </span>
                    <x-money :amount="$allocation->amount" class="font-semibold {{ $allocation->amount < 0 ? 'text-alert' : '' }}" />
                </li>
            @empty
                <li class="text-[13px] text-muted">Aucun paiement imputé.</li>
            @endforelse
        </ul>
    </aside>
</div>
@endsection
