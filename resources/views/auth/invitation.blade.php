@extends('layouts.auth')
@section('title', 'Invitation')

@section('content')
<p class="eyebrow !text-gold-deep">Invitation</p>
<h1 class="display mt-2 text-[30px] leading-tight">Rejoindre {{ $invitation->agency->name }}</h1>
<p class="mt-2 text-[14.5px] text-muted">
    {{ $invitation->inviter->fullName() }} vous invite en tant que
    <strong class="text-ink">{{ mb_strtolower(config('immora.roles.'.$invitation->role)) }}</strong>.
    Choisissez votre mot de passe pour activer votre accès.
</p>

<div class="mt-6 rounded-[10px] border border-line bg-paper px-4 py-3 text-[13.5px]">
    <p class="font-semibold">{{ $invitation->first_name }} {{ $invitation->last_name }}</p>
    <p class="text-muted">{{ $invitation->email ?? \App\Support\Phone::display($invitation->phone_e164) }}</p>
</div>

<form method="POST" action="{{ route('invitation.accept', $token) }}" class="mt-6 space-y-5">
    @csrf
    <x-field name="password" type="password" label="Mot de passe" autocomplete="new-password" required hint="10 caractères minimum, sans votre nom ni celui de l'agence." />
    <x-field name="password_confirmation" type="password" label="Confirmer le mot de passe" autocomplete="new-password" required />
    <button class="btn btn-primary h-12 w-full">Activer mon accès</button>
</form>
@endsection
