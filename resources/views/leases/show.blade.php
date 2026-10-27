@extends('layouts.app')
@section('title', 'Bail '.$lease->reference)

@section('content')
<x-page-header :title="$lease->tenant->displayName()" :kicker="'Bail '.$lease->reference.' · '.(\App\Models\Lease::TYPES[$lease->type] ?? $lease->type)" :back="route('leases.index')">
    <x-slot:subtitle>{{ $lease->unit->property->name }} · lot {{ $lease->unit->label }} · du {{ $lease->start_date->format('d/m/Y') }} au {{ $lease->end_date?->format('d/m/Y') ?? '—' }}</x-slot:subtitle>
    <x-slot:actions>
        <x-badge :status="$lease->status" :label="$lease->statusLabel()" class="!py-1.5 !px-3 !text-[12.5px]" />
        @if ($lease->status === 'draft')
            <form method="POST" action="{{ route('leases.activate', $lease) }}">@csrf
                <button class="btn btn-gold"><x-icon name="check" class="size-4" /> Activer le bail</button>
            </form>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
    <div class="space-y-6">
        @if ($lease->status === 'draft')
            <div class="card p-6">
                <p class="eyebrow !text-gold-deep">Aperçu</p>
                <h2 class="display mt-1 text-[21px]">Facture d'entrée dans les lieux</h2>
                <table class="table mt-4">
                    <tbody>
                        @foreach ($preview as $line)
                            <tr><td>{{ $line['label'] }}</td><td class="amount"><x-money :amount="$line['amount']" /></td></tr>
                        @endforeach
                        <tr><td class="display text-[17px]">Total à l'entrée</td><td class="amount display text-[20px]"><x-money :amount="collect($preview)->sum('amount')" /></td></tr>
                    </tbody>
                </table>
                <p class="mt-3 text-[12.5px] text-muted">À l'activation : la facture est créée, l'échéancier mensuel est généré et le lot passe « occupé ».</p>
            </div>
        @endif

        <div class="card overflow-hidden">
            <div class="border-b border-line px-5 py-4"><h2 class="display text-[19px]">Échéancier</h2></div>
            <table class="table">
                <thead><tr><th>Période</th><th>Échéance</th><th>Statut</th><th class="text-right">Montant</th><th class="text-right">Reste dû</th></tr></thead>
                <tbody>
                    @forelse ($lease->invoices as $invoice)
                        <tr class="row-link" data-href="{{ route('invoices.show', $invoice) }}">
                            <td class="font-semibold">{{ $invoice->periodLabel() }}</td>
                            <td class="text-graphite">{{ $invoice->due_date->format('d/m/Y') }}</td>
                            <td><x-badge :status="$invoice->status" :label="$invoice->statusLabel()" /></td>
                            <td class="amount"><x-money :amount="$invoice->total_amount" /></td>
                            <td class="amount {{ $invoice->balance() > 0 ? 'text-alert font-semibold' : 'text-faint' }}"><x-money :amount="$invoice->balance()" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty icon="calendar" title="Pas encore d'échéance" text="L'échéancier est généré à l'activation du bail." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <aside class="space-y-6">
        <div class="card p-5">
            <p class="eyebrow">Conditions</p>
            <dl class="mt-4 space-y-3 text-[13.5px]">
                <div class="flex justify-between"><dt class="text-muted">Loyer HT</dt><dd><x-money :amount="$lease->rent_amount" class="font-semibold" /></dd></div>
                <div class="flex justify-between"><dt class="text-muted">Charges</dt><dd><x-money :amount="$lease->charges_amount" class="font-semibold" /></dd></div>
                @if ((float) $lease->vat_rate > 0)<div class="flex justify-between"><dt class="text-muted">TVA</dt><dd class="font-semibold">{{ (float) $lease->vat_rate }} %</dd></div>@endif
                <div class="flex justify-between"><dt class="text-muted">Échéance</dt><dd class="font-semibold">le {{ $lease->due_day }} du mois</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Grâce · pénalité</dt><dd class="font-semibold">{{ $lease->grace_days }} j · {{ (float) $lease->penalty_rate }} %</dd></div>
                <div class="h-px bg-line"></div>
                <div class="flex justify-between"><dt class="text-muted">Caution</dt><dd><x-money :amount="$lease->deposit_amount" class="font-semibold" /></dd></div>
                <div class="flex justify-between"><dt class="text-muted">Avance</dt><dd><x-money :amount="$lease->advance_amount" class="font-semibold" /></dd></div>
                <div class="flex justify-between"><dt class="text-muted">Frais d'agence</dt><dd><x-money :amount="$lease->agency_fee_amount" class="font-semibold" /></dd></div>
            </dl>
        </div>

        @if ($lease->status === 'active')
            <form method="POST" action="{{ route('leases.notice', $lease) }}" class="card space-y-4 p-5">
                @csrf
                <p class="eyebrow">Préavis de départ</p>
                <x-field name="notice_date" type="date" label="Reçu le" :value="now()->toDateString()" required />
                <x-field name="end_date" type="date" label="Sortie prévue" :value="now()->addMonths(3)->toDateString()" required />
                <button class="btn btn-ghost w-full">Enregistrer le préavis</button>
            </form>
        @endif
        @if (in_array($lease->status, ['active', 'notice'], true))
            <form method="POST" action="{{ route('leases.terminate', $lease) }}" class="card space-y-4 p-5"
                data-confirm="Résilier ce bail ? Les échéances futures non payées seront annulées.">
                @csrf
                <p class="eyebrow !text-alert">Résiliation</p>
                <x-field name="end_date" type="date" label="Date de sortie effective" :value="optional($lease->end_date)->toDateString() ?? now()->toDateString()" required />
                <x-field name="reason" label="Motif" placeholder="Fin de bail, départ volontaire…" />
                <button class="btn btn-danger w-full">Résilier le bail</button>
            </form>
        @endif
    </aside>
</div>
@endsection
