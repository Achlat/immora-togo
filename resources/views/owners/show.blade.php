@extends('layouts.app')
@section('title', $owner->displayName())

@section('content')
<x-page-header :title="$owner->displayName()" :kicker="'Propriétaire · '.$owner->reference" :back="route('owners.index')">
    <x-slot:subtitle>{{ \App\Support\Phone::display($owner->phone_e164) }}@if ($owner->email) · {{ $owner->email }}@endif</x-slot:subtitle>
    <x-slot:actions>
        @unless ($hasPortal)
            <form method="POST" action="{{ route('owners.portal', $owner) }}">@csrf<button class="btn btn-ghost"><x-icon name="phone" class="size-4" /> Ouvrir son espace en ligne</button></form>
        @else
            <span class="inline-flex items-center gap-1.5 rounded-full bg-ok-wash px-3 py-1.5 text-[12px] font-bold text-ok"><x-icon name="check" class="size-3.5" /> Espace en ligne actif</span>
        @endunless
        <a href="{{ route('owners.edit', $owner) }}" class="btn btn-ghost">Modifier</a>
    </x-slot:actions>
</x-page-header>

<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
    <div class="space-y-6">
        <div class="card overflow-hidden">
            <div class="border-b border-line px-5 py-4"><h2 class="display text-[19px]">Patrimoine confié</h2></div>
            <table class="table">
                <thead><tr><th>Bien</th><th>Lots</th><th>Occupés</th></tr></thead>
                <tbody>
                    @forelse ($owner->properties as $property)
                        <tr class="row-link" data-href="{{ route('properties.show', $property) }}">
                            <td><p class="font-semibold">{{ $property->name }}</p><p class="text-[12px] text-muted">{{ $property->district }}</p></td>
                            <td class="num">{{ $property->units->count() }}</td>
                            <td class="num">{{ $property->units->where('status', 'occupied')->count() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><x-empty icon="building" title="Aucun bien rattaché" text="Rattachez un bien à ce propriétaire depuis sa fiche." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card overflow-hidden">
            <div class="border-b border-line px-5 py-4"><h2 class="display text-[19px]">Relevés de gérance</h2></div>
            <table class="table">
                <thead><tr><th>Période</th><th>Statut</th><th class="text-right">Encaissé</th><th class="text-right">Reversé</th></tr></thead>
                <tbody>
                    @forelse ($owner->statements as $statement)
                        <tr class="row-link" data-href="{{ route('statements.show', $statement) }}">
                            <td class="font-semibold">{{ ucfirst($statement->period_start->translatedFormat('F Y')) }}</td>
                            <td><x-badge :status="$statement->status" :label="$statement->statusLabel()" /></td>
                            <td class="amount"><x-money :amount="$statement->collected_amount" /></td>
                            <td class="amount font-semibold"><x-money :amount="$statement->payout_amount" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty icon="statement" title="Aucun relevé" text="Les relevés se génèrent chaque mois depuis le menu Relevés de gérance." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <aside class="space-y-6">
        <div class="card p-5">
            <p class="eyebrow">Mandat {{ $owner->mandate?->reference }}</p>
            @if ($owner->mandate)
                <dl class="mt-4 space-y-3 text-[13.5px]">
                    <div class="flex justify-between"><dt class="text-muted">Commission</dt><dd class="font-semibold">{{ rtrim(rtrim(number_format((float) $owner->mandate->commission_rate, 2, ',', ''), '0'), ',') }} % HT{{ $owner->mandate->commission_vat ? ' + TVA' : '' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Assiette</dt><dd class="font-semibold">{{ $owner->mandate->commission_base === 'rent' ? 'Loyers' : 'Loyers + charges' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Plafond dépenses</dt><dd><x-money :amount="$owner->mandate->expense_ceiling_amount" class="font-semibold" /></dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Réserve</dt><dd><x-money :amount="$owner->mandate->reserve_amount" class="font-semibold" /></dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Échéance</dt><dd class="font-semibold">{{ $owner->mandate->endDate()->format('d/m/Y') }}</dd></div>
                </dl>
            @endif
        </div>
        <div class="card p-5">
            <p class="eyebrow">Reversements</p>
            <p class="mt-3 font-semibold">{{ ['transfer' => 'Virement bancaire', 'mobile_money' => 'Mobile Money', 'cash' => 'Espèces', 'cheque' => 'Chèque'][$owner->payout_method] ?? '—' }}</p>
            <p class="mt-1 break-all font-mono text-[12px] text-muted">{{ $owner->payout_details ?? 'Coordonnées non renseignées' }}</p>
        </div>
    </aside>
</div>
@endsection
