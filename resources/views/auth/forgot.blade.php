@extends('layouts.auth')
@section('title', 'Mot de passe oublié')

@section('content')
<a href="{{ route('login') }}" class="mb-8 inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-muted hover:text-ink">
    <x-icon name="arrow-left" class="size-3.5" /> Retour à la connexion
</a>
<h1 class="display text-[32px] leading-tight">Mot de passe oublié</h1>
<p class="mt-2 text-[14.5px] text-muted">Indiquez votre e-mail ou votre numéro. Nous vous envoyons un lien pour choisir un nouveau mot de passe.</p>

<div class="mt-8"><x-flash /></div>

<form method="POST" action="{{ route('password.email') }}" class="space-y-5">
    @csrf
    <x-field name="identifier" label="E-mail ou numéro de téléphone" autocomplete="username" autofocus placeholder="vous@prestige.tg ou +228 90 12 34 56" />
    <button class="btn btn-primary h-12 w-full">Recevoir le lien</button>
</form>
@endsection
