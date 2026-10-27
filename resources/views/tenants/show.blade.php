@extends('layouts.app')
@section('title', $tenant->displayName())

@section('content')
<x-page-header :title="$tenant->displayName()" :kicker="'Locataire · '.$tenant->reference" :back="route('tenants.index')">
    <x-slot:subtitle>{{ \App\Support\Phone::display($tenant->phone_e164) }}@if ($tenant->profession) · {{ $tenant->profession }}@endif</x-slot:subtitle>
    <x-slot:actions>
        @unless ($tenant->user_id)
            <form method="POST" action="{{ route('tenants.portal', $tenant) }}">@csrf<button class="btn btn-ghost"><x-icon name="phone" class="size-4" /> Ouvrir son espace</button></form>
        @endunless
        <a href="{{ route('tenants.edit', $tenant) }}" class="btn btn-ghost">Modifier</a>
        @can('payments.collect')
            <a href="{{ route('payments.create', ['tenant' => $tenant->id]) }}" class="btn btn-gold"><x-icon name="plus" class="size-4" /> Encaisser</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <x-stat label="Solde dû" :value="number_format($balance, 0, ',', ' ')" unit="FCFA" :tone="$balance > 0 ? 'alert' : 'default'" :note="$balance > 0 ? 'À régulariser' : 'Locataire à jour'" />
    <x-stat label="Avance / avoir disponible" :value="number_format($credit, 0, ',', ' ')" unit="FCFA" note="Imputé automatiquement sur les prochaines échéances" />
    <x-stat label="Baux" :value="$tenant->leases->count()" :note="$tenant->leases->whereIn('status', ['active', 'notice'])->count().' en cours'" />
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="card overflow-hidden lg:col-span-2">
        <div class="flex items-center justify-between border-b border-line px-5 py-4">
            <h2 class="display text-[19px]">Baux</h2>
            <a href="{{ route('leases.create', ['tenant' => $tenant->id]) }}" class="link text-[12.5px]">Nouveau bail</a>
        </div>
        <table class="table">
            <thead><tr><th>Bail</th><th>Lot</th><th>Période</th><th>Statut</th><th class="text-right">Mensualité</th></tr></thead>
            <tbody>
                @forelse ($tenant->leases as $lease)
                    <tr class="row-link" data-href="{{ route('leases.show', $lease) }}">
                        <td class="font-mono text-[12.5px]">{{ $lease->reference }}</td>
                        <td><p class="font-semibold">{{ $lease->unit->label }}</p><p class="text-[12px] text-muted">{{ $lease->unit->property->name }}</p></td>
                        <td class="text-graphite">{{ $lease->start_date->format('d/m/Y') }} → {{ $lease->end_date?->format('d/m/Y') ?? '—' }}</td>
                        <td><x-badge :status="$lease->status" :label="$lease->statusLabel()" /></td>
                        <td class="amount"><x-money :amount="$lease->monthlyTotal()" /></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty icon="key" title="Aucun bail" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card overflow-hidden">
        <div class="border-b border-line px-5 py-4"><h2 class="display text-[19px]">Échéances</h2></div>
        <table class="table">
            <tbody>
                @foreach ($tenant->invoices as $invoice)
                    <tr class="row-link" data-href="{{ route('invoices.show', $invoice) }}">
                        <td><p class="font-semibold">{{ $invoice->periodLabel() }}</p><p class="text-[12px] text-muted">Échéance du {{ $invoice->due_date->format('d/m/Y') }}</p></td>
                        <td><x-badge :status="$invoice->status" :label="$invoice->statusLabel()" /></td>
                        <td class="amount"><x-money :amount="$invoice->status === 'paid' ? $invoice->total_amount : $invoice->balance()" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="card overflow-hidden">
        <div class="border-b border-line px-5 py-4"><h2 class="display text-[19px]">Paiements</h2></div>
        <table class="table">
            <tbody>
                @forelse ($tenant->payments as $payment)
                    <tr class="row-link" data-href="{{ route('payments.show', $payment) }}">
                        <td><p class="font-semibold">{{ $payment->paid_at->format('d/m/Y') }}</p><p class="text-[12px] text-muted">{{ $payment->methodLabel() }} · {{ $payment->number }}</p></td>
                        <td class="amount {{ $payment->amount < 0 ? 'text-alert' : '' }}"><x-money :amount="$payment->amount" /></td>
                    </tr>
                @empty
                    <tr><td><x-empty icon="receipt" title="Aucun paiement" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
