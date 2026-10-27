@extends('layouts.public')
@section('title', 'Vérification de document')

@section('content')
@if (! $found)
    <div class="card p-8 text-center">
        <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-alert-wash text-alert"><x-icon name="x" /></span>
        <h1 class="display mt-5 text-[26px]">Document introuvable</h1>
        <p class="mt-2 text-[14px] text-muted">Ce code ne correspond à aucune quittance ni aucun reçu valide. Le document a pu être annulé ou falsifié. Contactez l'agence émettrice.</p>
    </div>
@else
    <div class="card overflow-hidden">
        <div class="{{ $valid ? 'bg-ink' : 'bg-alert' }} px-8 py-6 text-white">
            <p class="eyebrow {{ $valid ? '!text-gold-light' : '!text-white/80' }}">Vérification Immora</p>
            <h1 class="display mt-2 text-[28px]">{{ $valid ? 'Document authentique' : 'Document annulé' }}</h1>
        </div>
        <dl class="divide-y divide-line px-8">
            <div class="flex justify-between py-3.5"><dt class="text-muted">Type</dt><dd class="font-semibold">{{ $type }}</dd></div>
            <div class="flex justify-between py-3.5"><dt class="text-muted">Numéro</dt><dd class="font-mono font-semibold">{{ $number ?? '—' }}</dd></div>
            <div class="flex justify-between py-3.5"><dt class="text-muted">Montant</dt><dd><x-money :amount="$amount" class="font-bold" /></dd></div>
            @if ($period)<div class="flex justify-between py-3.5"><dt class="text-muted">Période</dt><dd class="font-semibold">{{ $period }}</dd></div>@endif
            <div class="flex justify-between py-3.5"><dt class="text-muted">Date</dt><dd class="font-semibold">{{ $date?->format('d/m/Y') }}</dd></div>
            <div class="flex justify-between py-3.5"><dt class="text-muted">Locataire</dt><dd class="font-semibold">{{ $initials }}</dd></div>
            <div class="flex justify-between py-3.5"><dt class="text-muted">Émis par</dt><dd class="font-semibold">{{ $agency }}</dd></div>
        </dl>
        <p class="border-t border-line bg-paper px-8 py-4 text-[12px] text-muted">Seules les initiales du locataire sont affichées, pour protéger ses données personnelles.</p>
    </div>
@endif
@endsection
