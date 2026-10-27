@extends('layouts.app')
@section('title', 'Baux')

@section('content')
<x-page-header title="Baux" kicker="Patrimoine">
    <x-slot:subtitle>Un bail porte toujours sur un lot : caution, avance et échéancier sont calculés à l'activation.</x-slot:subtitle>
    <x-slot:actions><a href="{{ route('leases.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nouveau bail</a></x-slot:actions>
</x-page-header>

<div class="mb-4 flex flex-wrap gap-2">
    @foreach (['active' => 'En cours', 'draft' => 'Brouillons', 'terminated' => 'Résiliés', 'all' => 'Tous'] as $value => $label)
        <a href="{{ route('leases.index', ['status' => $value]) }}"
            class="rounded-full border px-3.5 py-1.5 text-[12.5px] font-semibold {{ $status === $value ? 'border-ink bg-ink text-white' : 'border-line bg-white text-graphite hover:border-ink' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Référence</th><th>Locataire</th><th>Lot</th><th>Début</th><th>Fin</th><th>Statut</th><th class="text-right">Mensualité</th></tr></thead>
            <tbody>
                @forelse ($leases as $lease)
                    <tr class="row-link" data-href="{{ route('leases.show', $lease) }}">
                        <td class="font-mono text-[12.5px]">{{ $lease->reference }}</td>
                        <td class="font-semibold">{{ $lease->tenant->displayName() }}</td>
                        <td><p>{{ $lease->unit->label }}</p><p class="text-[12px] text-muted">{{ $lease->unit->property->name }}</p></td>
                        <td class="text-graphite">{{ $lease->start_date->format('d/m/Y') }}</td>
                        <td class="text-graphite">{{ $lease->end_date?->format('d/m/Y') ?? '—' }}</td>
                        <td><x-badge :status="$lease->status" :label="$lease->statusLabel()" /></td>
                        <td class="amount"><x-money :amount="$lease->monthlyTotal()" /></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty icon="key" title="Aucun bail dans cette liste" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $leases->links() }}</div>
@endsection
