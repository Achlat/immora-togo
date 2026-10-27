<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Immora') · {{ $currentAgency->name }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper text-ink" x-data="{ drawer: false }">
@php
    $u = auth()->user();
    $nav = [
        'Pilotage' => [
            ['dashboard', 'home', 'Tableau de bord', 'dashboard', 'dashboard.view'],
            ['reports.arrears', 'chart', 'Impayés', 'reports.*', 'reports.view'],
        ],
        'Patrimoine' => [
            ['properties.index', 'building', 'Biens & lots', 'properties.*|units.*', 'properties.view'],
            ['owners.index', 'crown', 'Propriétaires', 'owners.*', 'owners.manage'],
            ['tenants.index', 'users', 'Locataires', 'tenants.*', 'tenants.manage'],
            ['leases.index', 'key', 'Baux', 'leases.*', 'leases.manage'],
        ],
        'Finance' => [
            ['invoices.index', 'file', 'Échéances', 'invoices.*', 'payments.collect'],
            ['payments.index', 'receipt', 'Encaissements', 'payments.*', 'payments.collect'],
            ['cash.index', 'vault', 'Caisse', 'cash.*', 'payments.collect'],
            ['expenses.index', 'tool', 'Dépenses', 'expenses.*', 'expenses.manage'],
            ['statements.index', 'statement', 'Relevés de gérance', 'statements.*', 'statements.manage'],
        ],
        'Agence' => [
            ['team.index', 'team', 'Équipe', 'team.*', 'team.manage'],
            ['settings.edit', 'settings', 'Paramètres', 'settings.*', 'settings.manage'],
            ['security.index', 'shield', 'Sécurité', 'security.*', null],
        ],
    ];
@endphp

<div class="flex min-h-screen">
    {{-- Barre latérale --}}
    <div x-show="drawer" x-cloak class="fixed inset-0 z-30 bg-ink/50 lg:hidden" @click="drawer = false"></div>
    <aside class="fixed inset-y-0 left-0 z-40 flex w-[256px] -translate-x-full flex-col bg-ink transition-transform lg:translate-x-0"
        :class="drawer && '!translate-x-0'">
        <div class="flex h-[68px] items-center px-6">
            <a href="{{ route('dashboard') }}"><x-logo tone="light" /></a>
        </div>

        <div class="mx-4 mb-4 rounded-[10px] border border-white/[0.07] bg-white/[0.03] px-3.5 py-3">
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-gold-light/80">Agence</p>
            <p class="mt-0.5 truncate text-[13.5px] font-semibold text-white">{{ $currentAgency->name }}</p>
            @if ($u->activeAgencies()->count() > 1)
                <a href="{{ route('agency.select') }}" class="mt-1 inline-flex items-center gap-1 text-[11.5px] font-semibold text-[#9b978d] hover:text-white">
                    <x-icon name="swap" class="size-3" /> Changer d'agence
                </a>
            @endif
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-4 pb-6" aria-label="Navigation principale">
            @foreach ($nav as $group => $items)
                @php($visible = collect($items)->filter(fn ($i) => $i[4] === null || Gate::allows($i[4])))
                @if ($visible->isNotEmpty())
                    <div>
                        <p class="mb-1.5 px-3 text-[10px] font-bold uppercase tracking-[0.2em] text-[#6d6a63]">{{ $group }}</p>
                        <div class="space-y-0.5">
                            @foreach ($visible as [$route, $icon, $label, $match])
                                <x-nav-link :route="$route" :icon="$icon" :match="explode('|', $match)">{{ $label }}</x-nav-link>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>

        <div class="border-t border-white/[0.07] p-4">
            <div class="flex items-center gap-3">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full border border-gold/40 text-[12px] font-bold text-gold-light">{{ $u->initials() }}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[13px] font-semibold text-white">{{ $u->fullName() }}</p>
                    <p class="truncate text-[11.5px] text-[#9b978d]">{{ config('immora.roles.'.$currentRole) }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-md p-2 text-[#9b978d] hover:bg-white/5 hover:text-white" title="Se déconnecter" aria-label="Se déconnecter">
                        <x-icon name="logout" class="size-[17px]" />
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Contenu --}}
    <div class="flex min-w-0 flex-1 flex-col lg:pl-[256px]">
        <header class="sticky top-0 z-20 flex h-[68px] items-center gap-3 border-b border-line bg-paper/90 px-4 backdrop-blur md:px-8"
            x-data="commandPalette('{{ route('search') }}')" @keydown.window.ctrl.k.prevent="toggle()" @keydown.window.meta.k.prevent="toggle()">
            <button class="rounded-md p-2 text-ink lg:hidden" @click="drawer = true" aria-label="Ouvrir le menu">
                <x-icon name="menu" />
            </button>

            <button type="button" @click="toggle()"
                class="flex h-10 w-full max-w-[420px] items-center gap-2.5 rounded-[8px] border border-line bg-white px-3.5 text-left text-[13.5px] text-faint hover:border-line-strong">
                <x-icon name="search" class="size-4 text-muted" />
                <span class="flex-1 truncate">Rechercher un locataire, un lot, une quittance…</span>
                <kbd class="hidden rounded border border-line bg-paper px-1.5 py-0.5 font-mono text-[10.5px] text-muted sm:inline">Ctrl K</kbd>
            </button>

            <div class="ml-auto flex items-center gap-2">
                @if (isset($subscription) && $subscription?->status === 'trial')
                    <span class="hidden items-center gap-1.5 rounded-full border border-gold/40 bg-gold-wash px-3 py-1 text-[12px] font-semibold text-gold-deep md:inline-flex">
                        Essai · {{ $subscription->daysLeft() }} j restants
                    </span>
                @endif
                @can('payments.collect')
                    <a href="{{ route('payments.create') }}" class="btn btn-gold">
                        <x-icon name="plus" class="size-4" /> <span class="hidden sm:inline">Encaisser</span>
                    </a>
                @endcan
            </div>

            {{-- Palette de recherche --}}
            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center bg-ink/40 px-4 pt-[12vh]" @keydown.escape.window="close()">
                <div class="w-full max-w-[560px] overflow-hidden rounded-[14px] bg-white shadow-lift" @click.outside="close()">
                    <div class="flex items-center gap-3 border-b border-line px-4">
                        <x-icon name="search" class="size-4 text-muted" />
                        <input x-ref="search" x-model="query" @input="search()" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.enter.prevent="go()"
                            class="h-14 flex-1 bg-transparent text-[15px] outline-none placeholder:text-faint" placeholder="Nom, téléphone, référence de lot, n° de quittance…" aria-label="Recherche globale">
                        <kbd class="rounded border border-line px-1.5 py-0.5 font-mono text-[10.5px] text-muted">Échap</kbd>
                    </div>
                    <ul class="max-h-[360px] overflow-y-auto p-2" role="listbox">
                        <template x-for="(item, index) in results" :key="item.url">
                            <li>
                                <a :href="item.url" class="flex items-center gap-3 rounded-lg px-3 py-2.5" :class="index === active ? 'bg-ivory' : ''" @mouseenter="active = index">
                                    <span class="rounded bg-ink px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-gold-light" x-text="item.type"></span>
                                    <span class="flex-1 truncate text-[14px] font-semibold" x-text="item.label"></span>
                                    <span class="truncate text-[12.5px] text-muted" x-text="item.meta"></span>
                                </a>
                            </li>
                        </template>
                        <li x-show="query.length >= 2 && results.length === 0" class="px-3 py-8 text-center text-[13.5px] text-muted">Aucun résultat pour cette recherche.</li>
                        <li x-show="query.length < 2" class="px-3 py-8 text-center text-[13.5px] text-muted">Tapez au moins 2 caractères.</li>
                    </ul>
                </div>
            </div>
        </header>

        @if (isset($subscription) && $subscription && ! $subscription->isActive())
            <div class="border-b border-alert/20 bg-alert-wash px-8 py-2.5 text-[13px] font-semibold text-alert">
                Abonnement expiré : votre espace est en lecture seule. Vos données sont conservées.
            </div>
        @endif

        <main class="mx-auto w-full max-w-[1280px] flex-1 px-4 py-8 md:px-8 md:py-10">
            <x-flash />
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
