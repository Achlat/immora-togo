@extends('layouts.auth')
@section('title', 'Activer la double authentification')

@section('content')
<x-flash />

@if (! empty($codes))
    <span class="flex size-11 items-center justify-center rounded-full bg-ok-wash text-ok"><x-icon name="check" /></span>
    <h1 class="display mt-5 text-[30px] leading-tight">Double authentification activée</h1>
    <p class="mt-2 text-[14.5px] text-muted">Conservez ces 10 codes de secours en lieu sûr. Ils vous permettent de vous connecter si vous perdez votre téléphone. Chaque code ne sert qu'une fois.</p>
    <div class="mt-6 grid grid-cols-2 gap-2 rounded-[10px] border border-line bg-paper p-4 font-mono text-[14px]">
        @foreach ($codes as $code)<span class="num">{{ $code }}</span>@endforeach
    </div>
    <p class="hint">Ces codes ne seront plus affichés. Imprimez-les ou notez-les maintenant.</p>
    <a href="{{ route('dashboard') }}" class="btn btn-primary mt-7 h-12 w-full">J'ai noté mes codes, continuer</a>
@elseif ($enabled)
    <span class="flex size-11 items-center justify-center rounded-full bg-ok-wash text-ok"><x-icon name="shield" /></span>
    <h1 class="display mt-5 text-[30px] leading-tight">Votre compte est protégé</h1>
    <p class="mt-2 text-[14.5px] text-muted">La double authentification est active sur votre compte depuis le {{ auth()->user()->two_factor_confirmed_at->format('d/m/Y') }}.</p>
    <a href="{{ route('dashboard') }}" class="btn btn-primary mt-7 h-12 w-full">Retour à mon espace</a>
@else
    <p class="eyebrow !text-gold-deep">Sécurité du compte</p>
    <h1 class="display mt-2 text-[30px] leading-tight">Activer la double authentification</h1>
    <p class="mt-2 text-[14.5px] text-muted">Un code temporaire, généré par votre téléphone, protège l'accès aux finances de l'agence même si votre mot de passe fuite.</p>

    <ol class="mt-7 space-y-5 text-[14px]">
        <li class="flex gap-3">
            <span class="display flex size-7 shrink-0 items-center justify-center rounded-full bg-ink text-[13px] text-gold-light">1</span>
            <span>Installez <strong>Google Authenticator</strong> ou <strong>Microsoft Authenticator</strong> sur votre téléphone.</span>
        </li>
        <li class="flex gap-3">
            <span class="display flex size-7 shrink-0 items-center justify-center rounded-full bg-ink text-[13px] text-gold-light">2</span>
            <div>
                <span>Scannez ce QR code avec l'application.</span>
                <div class="mt-3 flex items-center gap-4">
                    <img src="{{ $qr }}" alt="QR code de configuration" class="size-36 rounded-lg border border-line bg-white p-1.5">
                    <div class="text-[12px] text-muted">Impossible de scanner ?<br>Saisissez cette clé :<br><code class="mt-1 block font-mono text-[12.5px] font-semibold text-ink">{{ $secret }}</code></div>
                </div>
            </div>
        </li>
        <li class="flex gap-3">
            <span class="display flex size-7 shrink-0 items-center justify-center rounded-full bg-ink text-[13px] text-gold-light">3</span>
            <span>Saisissez le code à 6 chiffres affiché.</span>
        </li>
    </ol>

    <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-6 space-y-4">
        @csrf
        <input name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" aria-label="Code à 6 chiffres"
            class="input num h-12 text-center text-[20px] font-bold tracking-[0.3em]" placeholder="000000" @error('code') aria-invalid="true" @enderror>
        @error('code')<p class="error" role="alert">{{ $message }}</p>@enderror
        <button class="btn btn-primary h-12 w-full">Activer</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button class="text-[12.5px] font-semibold text-muted hover:text-ink">Se déconnecter</button>
    </form>
@endif
@endsection
