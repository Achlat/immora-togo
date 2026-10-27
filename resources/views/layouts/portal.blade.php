<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d0d0e">
    <title>@yield('title', 'Mon espace') · {{ $currentAgency->name }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper text-ink">
    <header class="bg-ink">
        <div class="mx-auto flex h-16 max-w-[760px] items-center justify-between px-5">
            <div class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-lg border border-gold/40 text-[13px] font-bold text-gold-light">{{ $currentAgency->initials() }}</span>
                <div class="leading-tight">
                    <p class="text-[14px] font-semibold text-white">{{ $currentAgency->name }}</p>
                    <p class="text-[11.5px] text-[#9b978d]">Espace {{ $currentRole === 'owner' ? 'propriétaire' : 'locataire' }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-2 text-[12.5px] font-semibold text-[#b9b5ab] hover:text-white">
                    <x-icon name="logout" class="size-4" /> Quitter
                </button>
            </form>
        </div>
    </header>
    <main class="mx-auto max-w-[760px] px-5 py-8">
        <x-flash />
        @yield('content')
    </main>
    <footer class="mx-auto max-w-[760px] px-5 pb-10 text-center text-[11.5px] text-faint">
        {{ $currentAgency->name }} · {{ \App\Support\Phone::display($currentAgency->phone) }} — propulsé par Immora
    </footer>
</body>
</html>
