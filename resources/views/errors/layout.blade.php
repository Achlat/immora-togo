<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('code') · Immora</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-paper px-6 text-ink">
    <main class="w-full max-w-[460px] text-center">
        <x-logo class="justify-center" />
        <p class="display mt-12 text-[88px] leading-none text-gold">@yield('code')</p>
        <div class="mx-auto my-6 h-px w-16 bg-gold"></div>
        <h1 class="display text-[28px] leading-tight">@yield('heading')</h1>
        <p class="mt-3 text-[14.5px] text-muted">@yield('message')</p>
        <div class="mt-8 flex justify-center gap-2">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}" class="btn btn-ghost">Revenir en arrière</a>
            <a href="{{ url('/') }}" class="btn btn-primary">Accueil</a>
        </div>
    </main>
</body>
</html>
