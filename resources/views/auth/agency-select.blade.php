@extends('layouts.auth')
@section('title', 'Choisir un espace')

@section('content')
<p class="eyebrow !text-gold-deep">Espaces de travail</p>
<h1 class="display mt-2 text-[30px] leading-tight">Choisissez votre agence</h1>
<p class="mt-2 text-[14.5px] text-muted">Votre compte est rattaché à plusieurs agences.</p>

<div class="mt-8 space-y-3">
    @forelse ($agencies as $agency)
        <form method="POST" action="{{ route('agency.switch') }}">
            @csrf
            <input type="hidden" name="agency_id" value="{{ $agency->id }}">
            <button class="group flex w-full items-center gap-4 rounded-[12px] border border-line bg-white px-4 py-3.5 text-left transition hover:border-ink">
                <span class="flex size-10 items-center justify-center rounded-lg bg-ink text-[13px] font-bold text-gold-light">{{ $agency->initials() }}</span>
                <span class="flex-1">
                    <span class="block font-semibold">{{ $agency->name }}</span>
                    <span class="text-[12.5px] text-muted">{{ config('immora.roles.'.$agency->pivot->role) }} · {{ $agency->city ?? config('immora.countries.'.$agency->country_code.'.name') }}</span>
                </span>
                <x-icon name="arrow-right" class="size-4 text-faint group-hover:text-ink" />
            </button>
        </form>
    @empty
        <p class="text-muted">Aucune agence active n'est rattachée à votre compte.</p>
    @endforelse
</div>

<form method="POST" action="{{ route('logout') }}" class="mt-8">
    @csrf
    <button class="text-[13px] font-semibold text-muted hover:text-ink">Se déconnecter</button>
</form>
@endsection
