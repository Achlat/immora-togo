@extends('layouts.auth')
@section('title', 'Code de connexion')

@section('content')
<a href="{{ route('login', ['mode' => 'sms']) }}" class="mb-8 inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-muted hover:text-ink">
    <x-icon name="arrow-left" class="size-3.5" /> Changer de numéro
</a>

<p class="eyebrow !text-gold-deep">Vérification</p>
<h1 class="display mt-2 text-[32px] leading-tight">Saisissez votre code</h1>
<p class="mt-2 text-[14.5px] text-muted">Nous avons envoyé un code à 6 chiffres au <strong class="num text-ink">{{ $masked }}</strong>.</p>

<div class="mt-8"><x-flash /></div>

<form method="POST" action="{{ route('otp.verify') }}" x-data="otpInput(6)" class="mt-2">
    @csrf
    <input type="hidden" name="code" :value="value">
    <div class="flex justify-between gap-2" @paste="paste($event)">
        @for ($i = 0; $i < 6; $i++)
            <input x-ref="d{{ $i }}" type="text" inputmode="numeric" maxlength="1" @if ($i === 0) autocomplete="one-time-code" autofocus @endif
                aria-label="Chiffre {{ $i + 1 }}"
                class="input num h-14 w-full max-w-[56px] text-center text-[22px] font-bold"
                @input="input({{ $i }}, $event)" @keydown.backspace="back({{ $i }}, $event)">
        @endfor
    </div>
    @error('code')<p class="error mt-3" role="alert">{{ $message }}</p>@enderror

    <button type="submit" class="btn btn-primary mt-7 h-12 w-full text-[14.5px]">Valider le code</button>
</form>

<div class="mt-6 text-center text-[13px] text-muted" x-data="countdown({{ $resendIn }})">
    <span x-show="left > 0">Renvoyer le code dans <strong class="num text-ink" x-text="label"></strong></span>
    <form x-show="left === 0" x-cloak method="POST" action="{{ route('otp.send') }}">
        @csrf
        <input type="hidden" name="phone" value="{{ $phone }}">
        <button class="link">Renvoyer un code</button>
    </form>
</div>
@endsection
