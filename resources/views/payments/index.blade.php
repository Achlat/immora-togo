@extends('layouts.app')
@section('title', 'Encaissements')

@section('content')
<x-page-header title="Encaissements" kicker="Finance">
    <x-slot:subtitle>Aujourd'hui : <strong class="text-ink"><x-money :amount="$todayTotal" /></strong> encaissés.</x-slot:subtitle>
    <x-slot:actions><a href="{{ route('payments.create') }}" class="btn btn-gold"><x-icon name="plus" class="size-4" /> Nouvel encaissement</a></x-slot:actions>
</x-page-header>

<form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
    <div><label class="label">Mode</label>
        <select name="method" class="input h-9 w-auto text-[13px]">
            <option value="">Tous</option>
            @foreach (config('immora.payment_methods') as $value => $label)
                <option value="{{ $value }}" @selected(request('method') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div><label class="label">Du</label><input type="date" name="from" value="{{ request('from') }}" class="input h-9 text-[13px]"></div>
    <div><label class="label">Au</label><input type="date" name="to" value="{{ request('to') }}" class="input h-9 text-[13px]"></div>
    <button class="btn btn-ghost btn-sm mb-0.5">Filtrer</button>
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Reçu</th><th>Date</th><th>Locataire</th><th>Mode</th><th>Saisi par</th><th class="text-right">Montant</th></tr></thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr class="row-link" data-href="{{ route('payments.show', $payment) }}">
                        <td class="font-mono text-[12px]">{{ $payment->number }}</td>
                        <td class="text-graphite">{{ $payment->paid_at->format('d/m/Y H:i') }}</td>
                        <td class="font-semibold">{{ $payment->tenant->displayName() }}</td>
                        <td>{{ $payment->methodLabel() }}@if ($payment->isReversed()) <x-badge status="reversed" label="Annulé" class="ml-1" />@endif</td>
                        <td class="text-graphite">{{ $payment->creator?->fullName() ?? 'En ligne' }}</td>
                        <td class="amount font-semibold {{ $payment->amount < 0 ? 'text-alert' : '' }}"><x-money :amount="$payment->amount" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty icon="receipt" title="Aucun encaissement" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $payments->links() }}</div>
@endsection
