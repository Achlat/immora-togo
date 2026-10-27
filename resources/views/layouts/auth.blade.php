<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Connexion') · Immora</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-white text-ink">
<div class="grid min-h-screen lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]">

    {{-- Panneau de marque (masqué sous 1024 px) --}}
    <aside class="relative hidden overflow-hidden bg-ink lg:flex lg:flex-col">
        <svg class="pointer-events-none absolute -right-24 bottom-0 h-[78%] text-gold-light/[0.13]" viewBox="0 0 400 520" fill="none" aria-hidden="true">
            <path d="M40 520V200a160 160 0 0 1 320 0v320" stroke="currentColor"/>
            <path d="M90 520V210a110 110 0 0 1 220 0v310" stroke="currentColor"/>
            <path d="M140 520V220a60 60 0 0 1 120 0v300" stroke="currentColor"/>
            <path d="M200 520V80" stroke="currentColor"/>
            <path d="M0 519h400" stroke="currentColor"/>
        </svg>

        <div class="relative flex flex-1 flex-col px-14 py-12">
            <x-logo tone="light" size="lg" />

            <div class="mt-auto max-w-[460px]">
                <div class="mb-8 h-px w-16 bg-gold-light"></div>
                <h2 class="display text-[44px] leading-[1.05] text-white">
                    Vos loyers encaissés à l'heure.<br>
                    <span class="text-gold-light" style="font-style:italic;font-weight:380">Vos propriétaires rassurés.</span>
                </h2>
                <p class="mt-6 max-w-[400px] text-[15px] leading-relaxed text-[#a9a59b]">
                    Gérance locative, encaissements Mobile Money et relevés propriétaires — réunis dans un seul outil, pensé pour les agences d'Afrique francophone.
                </p>

                <dl class="mt-12 grid grid-cols-3 gap-6 border-t border-white/10 pt-6">
                    <div><dt class="text-[11.5px] text-[#8d897f]">Quittance envoyée</dt><dd class="display mt-1 text-[24px] text-white">&lt; 1 min</dd></div>
                    <div><dt class="text-[11.5px] text-[#8d897f]">Relevés du mois</dt><dd class="display mt-1 text-[24px] text-white">1 clic</dd></div>
                    <div><dt class="text-[11.5px] text-[#8d897f]">Montants</dt><dd class="display mt-1 text-[24px] text-white">FCFA</dd></div>
                </dl>
            </div>
        </div>
    </aside>

    {{-- Formulaire --}}
    <main class="flex flex-col px-6 py-8 sm:px-12">
        <div class="lg:hidden"><x-logo /></div>
        <div class="mx-auto flex w-full max-w-[400px] flex-1 flex-col justify-center py-10">
            @yield('content')
        </div>
        <footer class="mx-auto flex w-full max-w-[400px] flex-wrap items-center gap-x-4 gap-y-1 text-[12px] text-muted">
            <span class="inline-flex items-center gap-1.5"><x-icon name="lock" class="size-3.5" /> Connexion chiffrée</span>
            <span>© {{ date('Y') }} Immora</span>
            <a href="mailto:support@immora.app" class="hover:text-ink">Aide</a>
        </footer>
    </main>
</div>
</body>
</html>
