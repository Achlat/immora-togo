@extends('layouts.auth')
@section('title', 'Nouveau mot de passe')

@section('content')
<h1 class="display text-[32px] leading-tight">Nouveau mot de passe</h1>
<p class="mt-2 text-[14.5px] text-muted">10 caractères minimum. Une phrase facile à retenir est idéale : « Mon-bureau-du-Plateau-2026 ».</p>

<form method="POST" action="{{ route('password.update', $token) }}" class="mt-8 space-y-5">
    @csrf
    <x-field name="password" type="password" label="Nouveau mot de passe" autocomplete="new-password" required />
    <x-field name="password_confirmation" type="password" label="Confirmer le mot de passe" autocomplete="new-password" required />
    <button class="btn btn-primary h-12 w-full">Enregistrer et me connecter</button>
</form>
@endsection
