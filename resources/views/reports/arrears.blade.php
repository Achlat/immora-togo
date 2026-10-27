@extends('layouts.app')
@section('title', 'Impayés')

@section('content')
<x-page-header title="État des impayés" kicker="Pilotage">
    <x-slot:subtitle>Au {{ now()->format('d/m/Y') }} : <strong class="text-alert"><x-money :amount="$grandTotal" /></strong> dus par {{ $rows->count() }} locataire(s).</x-slot:subtitle>
</x-page-header>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Locataire</th><th>Lot</th><th>Plus ancien</th><th class="text-right">0–30 j</th><th class="text-right">31–60 j</th><th class="text-right">61–90 j</th><th class="text-right">+ 90 j</th><th class="text-right">Total</th><th></th></tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="row-link" data-href="{{ route('tenants.show', $row['tenant']) }}">
                        <td><p class="font-semibold">{{ $row['tenant']->displayName() }}</p><p class="num text-[12px] text-muted">{{ \App\Support\Phone::display($row['tenant']->phone_e164) }}</p></td>
                        <td class="text-graphite">{{ $row['unit']->reference }}</td>
                        <td class="text-graphite">{{ $row['oldest']->format('d/m/Y') }}</td>
                        @foreach ($row['buckets'] as $i => $amount)
                            <td class="amount {{ $amount > 0 ? ($i === 3 ? 'font-semibold text-alert' : '') : 'text-faint' }}">{{ $amount > 0 ? number_format($amount, 0, ',', ' ') : '—' }}</td>
                        @endforeach
                        <td class="amount font-bold text-alert"><x-money :amount="$row['total']" /></td>
                        <td class="text-right"><a href="{{ route('payments.create', ['tenant' => $row['tenant']->id]) }}" class="link text-[12.5px]">Encaisser</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9"><x-empty icon="check" title="Aucun impayé" text="Tous vos locataires sont à jour." /></td></tr>
                @endforelse
            </tbody>
            @if ($rows->isNotEmpty())
                <tfoot>
                    <tr class="bg-paper font-bold">
                        <td colspan="3" class="px-4 py-3">Total</td>
                        @foreach ($totals as $amount)<td class="amount px-4 py-3">{{ number_format($amount, 0, ',', ' ') }}</td>@endforeach
                        <td class="amount px-4 py-3 text-alert"><x-money :amount="$grandTotal" /></td><td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
