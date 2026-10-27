@extends('layouts.app')
@section('title', 'Locataires')

@section('content')
<x-page-header title="Locataires" kicker="Patrimoine">
    <x-slot:subtitle>Situation de chaque locataire, en temps réel.</x-slot:subtitle>
    <x-slot:actions><a href="{{ route('tenants.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nouveau locataire</a></x-slot:actions>
</x-page-header>

<div class="card overflow-hidden">
    <x-search-bar placeholder="Nom, téléphone…">
        <label class="inline-flex items-center gap-2 text-[13px] font-semibold text-graphite">
            <input type="checkbox" name="filter" value="arrears" class="size-4 accent-ink" @checked(request('filter') === 'arrears')> En retard uniquement
        </label>
    </x-search-bar>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Locataire</th><th>Téléphone</th><th>Logement</th><th class="text-right">Solde dû</th></tr></thead>
            <tbody>
                @forelse ($tenants as $tenant)
                    <tr class="row-link" data-href="{{ route('tenants.show', $tenant) }}">
                        <td><p class="font-semibold">{{ $tenant->displayName() }}</p><p class="font-mono text-[11.5px] text-muted">{{ $tenant->reference }}</p></td>
                        <td class="num text-graphite">{{ \App\Support\Phone::display($tenant->phone_e164) }}</td>
                        <td class="text-graphite">{{ $tenant->leases->map(fn ($l) => $l->unit->reference)->join(', ') ?: '—' }}</td>
                        <td class="amount">
                            @if ($tenant->balance_due > 0)
                                <x-money :amount="$tenant->balance_due" class="font-semibold text-alert" />
                            @else
                                <span class="inline-flex items-center gap-1 text-[12.5px] font-semibold text-ok"><x-icon name="check" class="size-3.5" /> À jour</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-empty icon="users" title="Aucun locataire" text="Ajoutez un locataire puis créez son bail.">
                        <a href="{{ route('tenants.create') }}" class="btn btn-primary">Ajouter un locataire</a></x-empty></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $tenants->links() }}</div>
@endsection
