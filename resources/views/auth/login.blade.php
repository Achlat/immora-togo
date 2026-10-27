@extends('layouts.auth')
@section('title', 'Connexion')

@if ($captcha && config('immora.turnstile.site_key'))
    @push('head')<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>@endpush
@endif

@section('content')
<div x-data="{ loading: false }">
    <p class="eyebrow !text-gold-deep">Espace agence</p>
    <h1 class="display mt-2 text-[34px] leading-tight">Bon retour parmi nous</h1>
    <p class="mt-2 text-[14.5px] text-muted">Connectez-vous à l'espace de votre agence.</p>

    <div class="mt-8">
        <x-flash />
    </div>

    {{-- Connexion : identifiant + mot de passe --}}
    <form method="POST" action="{{ url('/connexion') }}" @submit="loading = true" novalidate class="space-y-5">
        @csrf
        <div>
            <label for="identifier" class="label">E-mail ou numéro de téléphone</label>
            <input id="identifier" name="identifier" type="text" value="{{ old('identifier') }}" autocomplete="username" autofocus
                class="input" placeholder="vous@prestige.tg ou +228 90 12 34 56"
                @error('identifier') aria-invalid="true" aria-describedby="identifier_err" @enderror>
            @error('identifier')<p id="identifier_err" class="error" role="alert" aria-live="polite">{{ $message }}</p>@enderror
        </div>

        <div x-data="{ show: false, caps: false }">
            <div class="flex items-center justify-between">
                <label for="password" class="label">Mot de passe</label>
                <a href="{{ route('password.request') }}" class="link mb-1.5 text-[12.5px]">Mot de passe oublié ?</a>
            </div>
            <div class="relative">
                <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" autocomplete="current-password"
                    class="input pr-20" @keyup="caps = $event.getModifierState && $event.getModifierState('CapsLock')"
                    @error('password') aria-invalid="true" @enderror>
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3.5 text-[12.5px] font-bold text-ink hover:text-gold-deep"
                    :aria-label="show ? 'Masquer le mot de passe' : 'Afficher le mot de passe'" x-text="show ? 'Masquer' : 'Afficher'">Afficher</button>
            </div>
            <p x-show="caps" x-cloak class="hint !text-warn">Majuscules activées.</p>
            @error('password')<p class="error" role="alert">{{ $message }}</p>@enderror
        </div>

        <label class="flex cursor-pointer items-center gap-2.5 text-[13.5px] text-graphite">
            <input type="checkbox" name="remember" value="1" class="size-4 rounded border-line-strong accent-ink" @checked(old('remember'))>
            Rester connecté 30 jours sur cet appareil
        </label>

        @if ($captcha && config('immora.turnstile.site_key'))
            <div class="cf-turnstile" data-sitekey="{{ config('immora.turnstile.site_key') }}" data-language="fr"></div>
        @endif

        <button type="submit" class="btn btn-primary h-12 w-full text-[14.5px]" :disabled="loading">
            <span x-show="!loading">Se connecter</span>
            <span x-show="loading" x-cloak>Connexion…</span>
        </button>
    </form>
</div>
@endsection
