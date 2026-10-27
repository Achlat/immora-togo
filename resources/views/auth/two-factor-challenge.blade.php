@extends('layouts.auth')
@section('title', 'Double authentification')

@section('content')
<div x-data="{ recovery: false }">
    <span class="flex size-11 items-center justify-center rounded-full border border-gold/40 bg-gold-wash text-gold-deep"><x-icon name="shield" /></span>
    <h1 class="display mt-5 text-[32px] leading-tight">Double authentification</h1>
    <p class="mt-2 text-[14.5px] text-muted" x-show="!recovery">Saisissez le code affiché dans votre application d'authentification (Google Authenticator, Microsoft Authenticator…).</p>
    <p class="mt-2 text-[14.5px] text-muted" x-show="recovery" x-cloak>Saisissez l'un de vos codes de secours (format ABCD-EFGH). Chaque code ne sert qu'une fois.</p>

    <form method="POST" action="{{ url('/double-authentification') }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <label for="code" class="label" x-text="recovery ? 'Code de secours' : 'Code à 6 chiffres'">Code à 6 chiffres</label>
            <input id="code" name="code" type="text" autocomplete="one-time-code" autofocus
                :inputmode="recovery ? 'text' : 'numeric'" class="input num h-12 text-center text-[20px] font-bold tracking-[0.3em]"
                @error('code') aria-invalid="true" @enderror>
            @error('code')<p class="error" role="alert">{{ $message }}</p>@enderror
        </div>
        <label class="flex cursor-pointer items-center gap-2.5 text-[13.5px] text-graphite">
            <input type="checkbox" name="trust" value="1" class="size-4 accent-ink"> Faire confiance à cet appareil 30 jours
        </label>
        <button type="submit" class="btn btn-primary h-12 w-full">Vérifier</button>
    </form>

    <button type="button" class="link mt-6 text-[13px]" @click="recovery = !recovery"
        x-text="recovery ? 'Utiliser mon application' : 'Utiliser un code de secours'">Utiliser un code de secours</button>
</div>
@endsection
