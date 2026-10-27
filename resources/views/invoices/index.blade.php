@extends('layouts.app')
@section('title', 'Échéances')

@section('content')
<x-page-header title="Échéances" kicker="Finance">
    <x-slot:subtitle>Appels de loyers, quittances et relances. Les échéances du mois suivant sont générées automatiquement le 25.</x-slot:subtitle>
</x-page-header>

<div class="mb-4 flex flex-wrap items-center gap-2">
    @foreach (['open' => 'À encaisser', 'overdue' => 'En retard', 'paid' => 'Payées', 'all' => 'Toutes'] as $value => $label)
        <a href="{{ route('invoices.index', ['filter' => $value]) }}"
            class="rounded-full border px-3.5 py-1.5 text-[12.5px] font-semibold {{ $filter === $value ? 'border-ink bg-ink text-white' : 'border-line bg-white text-graphite hover:border-ink' }}">{{ $label }}</a>
    @endforeach
    <form method="GET" class="ml-auto flex items-center gap-2">
        <input type="hidden" name="filter" value="{{ $filter }}">
        <input type="month" name="month" value="{{ request('month') }}" class="input h-9 w-auto text-[13px]" aria-label="Filtrer par mois">
        <button class="btn btn-ghost btn-sm">Filtrer</button>
    </form>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>N°</th><th>Locataire</th><th>Période</th><th>Échéance</th><th>Statut</th><th class="text-right">Montant</th><th class="text-right">Reste dû</th><th></th></tr></thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr class="row-link" data-href="{{ route('invoices.show', $invoice) }}">
                        <td class="font-mono text-[12px]">{{ $invoice->receipt_number ?? $invoice->number }}</td>
                        <td><p class="font-semibold">{{ $invoice->tenant->displayName() }}</p><p class="text-[12px] text-muted">{{ $invoice->lease->unit->reference }}</p></td>
                        <td>{{ $invoice->periodLabel() }}</td>
                        <td class="text-graphite">{{ $invoice->due_date->format('d/m/Y') }}</td>
                        <td><x-badge :status="$invoice->status" :label="$invoice->statusLabel()" /></td>
                        <td class="amount"><x-money :amount="$invoice->total_amount" /></td>
                        <td class="amount {{ $invoice->balance() > 0 ? 'font-semibold text-alert' : 'text-faint' }}"><x-money :amount="$invoice->balance()" /></td>
                        <td class="text-right">
                            @if ($invoice->balance() > 0)
                                <a href="{{ route('payments.create', ['tenant' => $invoice->tenant_id]) }}" class="link text-[12.5px]">Encaisser</a>
                            @else
                                <a href="{{ route('invoices.pdf', $invoice) }}" class="link text-[12.5px]" target="_blank">Quittance</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty icon="file" title="Aucune échéance dans cette liste" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $invoices->links() }}</div>
@endsection
