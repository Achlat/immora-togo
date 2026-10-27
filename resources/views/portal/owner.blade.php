@extends('layouts.portal')
@section('title', 'Mon patrimoine')

@section('content')
<p class="eyebrow !text-gold-deep">Mon patrimoine</p>
<h1 class="display mt-1 text-[30px] leading-tight">Bonjour {{ $owner->first_name ?: $owner->displayName() }}</h1>
<p class="mt-1 text-[14px] text-muted">Vos biens confiés à {{ $currentAgency->name }}, en temps réel.</p>

<section class="mt-6 grid gap-3 sm:grid-cols-3">
    <div class="rounded-[14px] bg-ink p-5 text-white sm:col-span-1">
        <p class="eyebrow !text-gold-light/90">Encaissé ce mois</p>
        <p class="display num mt-2 text-[26px] leading-none">{{ number_format($collected, 0, ',', ' ') }}</p>
        <p class="mt-1 text-[11px] font-bold text-gold-light">FCFA</p>
    </div>
    <x-stat label="Loyers attendus / mois" :value="number_format($expected, 0, ',', ' ')" unit="FCFA" />
    <x-stat label="Occupation" :value="$occupied.'/'.$unitsCount" note="lots occupés" />
</section>

<section class="card mt-6 overflow-hidden">
    <div class="border-b border-line px-5 py-4"><h2 class="display text-[19px]">Mes biens</h2></div>
    @foreach ($owner->properties as $property)
        <div class="border-b border-line px-5 py-4 last:border-0">
            <p class="font-semibold">{{ $property->name }}</p>
            <p class="text-[12px] text-muted">{{ $property->district }}, {{ $property->city }}</p>
            <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                @foreach ($property->units as $unit)
                    <li class="flex items-center justify-between rounded-[8px] border border-line px-3 py-2 text-[13px]">
                        <span><span class="font-semibold">{{ $unit->label }}</span> <span class="text-muted">· {{ $unit->activeLease?->tenant->displayName() ?? 'Vacant' }}</span></span>
                        <x-badge :status="$unit->status" :label="$unit->statusLabel()" />
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</section>

<section class="card mt-6 overflow-hidden">
    <div class="border-b border-line px-5 py-4"><h2 class="display text-[19px]">Relevés de gérance</h2></div>
    <ul class="divide-y divide-line">
        @forelse ($statements as $statement)
            <li>
                <a href="{{ route('portal.statement.pdf', $statement) }}" target="_blank" class="flex items-center justify-between gap-3 px-5 py-3.5 hover:bg-paper">
                    <span><span class="font-semibold">{{ ucfirst($statement->period_start->translatedFormat('F Y')) }}</span>
                        <span class="block text-[12px] text-muted">Encaissé {{ number_format($statement->collected_amount, 0, ',', ' ') }} · {{ $statement->statusLabel() }}</span></span>
                    <span class="text-right"><x-money :amount="$statement->payout_amount" class="font-bold" /><span class="block text-[12px] font-semibold text-gold-deep">PDF</span></span>
                </a>
            </li>
        @empty
            <li class="px-5 py-6 text-center text-[13px] text-muted">Votre premier relevé sera disponible à la fin du mois.</li>
        @endforelse
    </ul>
</section>
@endsection
