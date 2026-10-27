@extends('layouts.app')
@section('title', 'Dépenses')

@section('content')
<x-page-header title="Dépenses" kicker="Finance">
    <x-slot:subtitle>Travaux, entretien, taxes : imputés au propriétaire, au locataire ou à l'agence.</x-slot:subtitle>
    <x-slot:actions>
        <form method="GET" class="flex items-center gap-2"><input type="month" name="month" value="{{ $month }}" class="input h-10 w-auto" aria-label="Mois"><button class="btn btn-ghost">Voir</button></form>
        <a href="{{ route('expenses.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nouvelle dépense</a>
    </x-slot:actions>
</x-page-header>

<div class="card overflow-hidden">
    <div class="flex items-baseline justify-between border-b border-line px-5 py-4">
        <h2 class="display text-[19px]">{{ ucfirst(\Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y')) }}</h2>
        <span class="text-[13px] text-muted">Total : <x-money :amount="$total" class="font-bold text-ink" /></span>
    </div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Date</th><th>Libellé</th><th>Catégorie</th><th>Bien</th><th>Imputée à</th><th class="text-right">Montant</th></tr></thead>
            <tbody>
                @forelse ($expenses as $expense)
                    <tr>
                        <td class="text-graphite">{{ $expense->spent_on->format('d/m/Y') }}</td>
                        <td><p class="font-semibold">{{ $expense->label }}</p>@if ($expense->supplier)<p class="text-[12px] text-muted">{{ $expense->supplier }}</p>@endif</td>
                        <td>{{ $expense->categoryLabel() }}</td>
                        <td class="text-graphite">{{ $expense->property?->name ?? '—' }}</td>
                        <td>{{ \App\Models\Expense::CHARGED_TO[$expense->charged_to] }}@if ($expense->owner) <span class="text-[12px] text-muted">· {{ $expense->owner->displayName() }}</span>@endif</td>
                        <td class="amount font-semibold"><x-money :amount="$expense->amount" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty icon="tool" title="Aucune dépense ce mois-ci" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $expenses->links() }}</div>
@endsection
