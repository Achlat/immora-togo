@extends('layouts.app')
@section('title', 'Biens')

@section('content')
<x-page-header title="Biens & lots" kicker="Patrimoine">
    <x-slot:subtitle>Immeubles, villas, cours communes : chaque bien regroupe des lots louables.</x-slot:subtitle>
    @can('properties.manage')
        <x-slot:actions><a href="{{ route('properties.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nouveau bien</a></x-slot:actions>
    @endcan
</x-page-header>

<div class="card overflow-hidden">
    <x-search-bar placeholder="Nom, référence, quartier…">
        <select name="type" class="input h-9 w-auto text-[13.5px]">
            <option value="">Tous les types</option>
            @foreach (config('immora.property_types') as $value => $label)
                <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </x-search-bar>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Bien</th><th>Type</th><th>Propriétaire</th><th>Localisation</th><th class="text-right">Occupation</th></tr></thead>
            <tbody>
                @forelse ($properties as $property)
                    @php($rate = $property->units_count > 0 ? round($property->occupied_count / $property->units_count * 100) : 0)
                    <tr class="row-link" data-href="{{ route('properties.show', $property) }}">
                        <td>
                            <p class="font-semibold">{{ $property->name }}</p>
                            <p class="font-mono text-[11.5px] text-muted">{{ $property->reference }}</p>
                        </td>
                        <td class="text-graphite">{{ $property->typeLabel() }}</td>
                        <td>{{ $property->owner?->displayName() ?? '—' }}</td>
                        <td class="text-graphite">{{ $property->district ? $property->district.', ' : '' }}{{ $property->city }}</td>
                        <td class="text-right">
                            <div class="ml-auto flex w-40 items-center justify-end gap-3">
                                <span class="h-1.5 flex-1 rounded-full bg-ivory"><span class="block h-1.5 rounded-full bg-ink" style="width: {{ $rate }}%"></span></span>
                                <span class="num text-[12.5px] font-semibold">{{ $property->occupied_count }}/{{ $property->units_count }}</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">
                        <x-empty icon="building" title="Aucun bien pour le moment" text="Créez votre premier bien puis ajoutez ses lots en une seule fois.">
                            @can('properties.manage')<a href="{{ route('properties.create') }}" class="btn btn-primary">Créer un bien</a>@endcan
                        </x-empty>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $properties->links() }}</div>
@endsection
