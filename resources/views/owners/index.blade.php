@extends('layouts.app')
@section('title', 'Propriétaires')

@section('content')
<x-page-header title="Propriétaires" kicker="Patrimoine">
    <x-slot:subtitle>Mandats de gestion, commissions et reversements.</x-slot:subtitle>
    <x-slot:actions><a href="{{ route('owners.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nouveau propriétaire</a></x-slot:actions>
</x-page-header>

<div class="card overflow-hidden">
    <x-search-bar placeholder="Nom ou raison sociale…" />
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Propriétaire</th><th>Contact</th><th>Biens</th><th>Commission</th><th>Reversement</th></tr></thead>
            <tbody>
                @forelse ($owners as $owner)
                    <tr class="row-link" data-href="{{ route('owners.show', $owner) }}">
                        <td>
                            <p class="font-semibold">{{ $owner->displayName() }}</p>
                            <p class="font-mono text-[11.5px] text-muted">{{ $owner->reference }}@if ($owner->country_of_residence && $owner->country_of_residence !== 'CI') · réside en {{ $owner->country_of_residence }}@endif</p>
                        </td>
                        <td class="text-graphite">{{ \App\Support\Phone::display($owner->phone_e164) }}</td>
                        <td class="num">{{ $owner->properties_count }}</td>
                        <td class="num">{{ $owner->mandate ? rtrim(rtrim(number_format((float) $owner->mandate->commission_rate, 2, ',', ''), '0'), ',').' %' : '—' }}</td>
                        <td class="text-graphite">{{ ['transfer' => 'Virement', 'mobile_money' => 'Mobile Money', 'cash' => 'Espèces', 'cheque' => 'Chèque'][$owner->payout_method] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty icon="crown" title="Aucun propriétaire" text="Enregistrez vos mandants et leurs conditions de gestion.">
                        <a href="{{ route('owners.create') }}" class="btn btn-primary">Ajouter un propriétaire</a></x-empty></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $owners->links() }}</div>
@endsection
