@extends('layouts.auth')
@section('title', 'Essai gratuit')

@section('content')
<p class="eyebrow !text-gold-deep">14 jours offerts · sans carte bancaire</p>
<h1 class="display mt-2 text-[32px] leading-tight">Créez l'espace de votre agence</h1>
<p class="mt-2 text-[14.5px] text-muted">Toutes les fonctions de l'offre Pro, avec vos propres données, dès aujourd'hui.</p>

<form method="POST" action="{{ url('/essai-gratuit') }}" class="mt-8 space-y-5" novalidate>
    @csrf
    <x-field name="agency_name" label="Nom de l'agence" required placeholder="Agence Horizon Immobilier" autocomplete="organization" />
    <x-field name="country_code" label="Pays" :options="$countries" value="CI" required />
    <div class="grid grid-cols-2 gap-4">
        <x-field name="first_name" label="Prénom" required autocomplete="given-name" />
        <x-field name="last_name" label="Nom" required autocomplete="family-name" />
    </div>
    <x-field name="email" type="email" label="E-mail professionnel" required autocomplete="email" />
    <x-field name="phone" type="tel" label="Téléphone" required inputmode="numeric" autocomplete="tel" placeholder="+228 90 12 34 56" />
    @error('phone_e164')<p class="error -mt-3" role="alert">{{ $message }}</p>@enderror
    <x-field name="password" type="password" label="Mot de passe" required autocomplete="new-password" hint="10 caractères minimum. Une phrase est idéale." />
    <x-field name="password_confirmation" type="password" label="Confirmer le mot de passe" required autocomplete="new-password" />

    <label class="flex items-start gap-2.5 text-[13px] text-graphite">
        <input type="checkbox" name="terms" value="1" class="mt-0.5 size-4 accent-ink" @checked(old('terms'))>
        <span>J'accepte les conditions générales d'utilisation et la politique de confidentialité d'Immora.</span>
    </label>
    @error('terms')<p class="error -mt-3" role="alert">{{ $message }}</p>@enderror

    <button class="btn btn-primary h-12 w-full">Démarrer mon essai gratuit</button>
</form>

<p class="mt-6 text-center text-[13px] text-muted">Déjà un compte ? <a href="{{ route('login') }}" class="link">Se connecter</a></p>
@endsection
