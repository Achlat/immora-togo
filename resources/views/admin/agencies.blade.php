<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Console éditeur · Immora</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper text-ink">
<header class="bg-ink">
    <div class="mx-auto flex h-16 max-w-[1280px] items-center justify-between px-6">
        <div class="flex items-center gap-4"><x-logo tone="light" /><span class="rounded-full border border-gold/40 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-[0.16em] text-gold-light">Console éditeur</span></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-[13px] font-semibold text-[#b9b5ab] hover:text-white">Se déconnecter</button></form>
    </div>
</header>
<main class="mx-auto max-w-[1280px] px-6 py-10">
    <x-flash />
    <x-page-header title="Espaces clients" kicker="Abonnements SaaS" />

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat label="MRR (revenu mensuel récurrent)" :value="number_format($mrr, 0, ',', ' ')" unit="FCFA" />
        <x-stat label="Agences" :value="$agencies->count()" :note="$trials.' en essai'" />
        <x-stat label="Lots gérés sur la plateforme" :value="number_format($unitCounts->sum(), 0, ',', ' ')" />
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Agence</th><th>Offre</th><th>Statut</th><th>Fin de période</th><th>Lots</th><th>Utilisateurs</th><th class="w-[360px]">Actions</th></tr></thead>
                <tbody>
                    @foreach ($agencies as $agency)
                        @php($sub = $agency->subscription)
                        <tr>
                            <td><p class="font-semibold">{{ $agency->name }}</p><p class="text-[12px] text-muted">{{ $agency->email }} · {{ $agency->country_code }}</p></td>
                            <td>{{ $sub?->plan->name ?? '—' }}</td>
                            <td><x-badge :status="$sub?->status ?? 'draft'" :label="['trial' => 'Essai', 'active' => 'Actif', 'suspended' => 'Suspendu'][$sub?->status] ?? '—'" /></td>
                            <td class="text-graphite">{{ $sub?->endsAt()?->format('d/m/Y') ?? '—' }}</td>
                            <td class="num">{{ $unitCounts[$agency->id] ?? 0 }}</td>
                            <td class="num">{{ $agency->users_count }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.agencies.extend', $agency) }}" class="flex flex-wrap items-center gap-1.5">
                                    @csrf
                                    <select name="plan_id" class="input h-8 w-auto text-[12px]">@foreach ($plans as $plan)<option value="{{ $plan->id }}" @selected($sub?->plan_id === $plan->id)>{{ $plan->name }}</option>@endforeach</select>
                                    <select name="billing_cycle" class="input h-8 w-auto text-[12px]"><option value="monthly">Mensuel</option><option value="yearly">Annuel</option></select>
                                    <input type="number" name="months" value="1" min="1" max="36" class="input h-8 w-14 text-[12px]" aria-label="Mois">
                                    <button class="btn btn-primary btn-sm">Activer</button>
                                </form>
                                <form method="POST" action="{{ route('admin.agencies.status', $agency) }}" class="mt-1.5">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ $agency->status === 'suspended' ? 'active' : 'suspended' }}">
                                    <button class="text-[12px] font-semibold {{ $agency->status === 'suspended' ? 'text-ok' : 'text-alert' }} hover:underline">{{ $agency->status === 'suspended' ? 'Réactiver' : 'Suspendre' }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>
