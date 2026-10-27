@extends('layouts.app')
@section('title', 'Sécurité')

@section('content')
<x-page-header title="Sécurité" kicker="Mon compte">
    <x-slot:actions>
        <a href="{{ route('two-factor.setup') }}" class="btn btn-ghost"><x-icon name="shield" class="size-4" />
            {{ auth()->user()->hasTwoFactor() ? 'Double authentification active' : 'Activer la double authentification' }}</a>
    </x-slot:actions>
</x-page-header>

<div class="card overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
        <div><h2 class="display text-[19px]">Appareils connectés</h2><p class="text-[12.5px] text-muted">Déconnectez un appareil perdu ou que vous ne reconnaissez pas.</p></div>
        <form method="POST" action="{{ route('security.revoke-others') }}" data-confirm="Déconnecter tous vos autres appareils ?">@csrf
            <button class="btn btn-ghost btn-sm">Déconnecter tous les autres</button>
        </form>
    </div>
    <table class="table">
        <tbody>
            @foreach ($sessions as $session)
                <tr>
                    <td>
                        <p class="font-semibold">{{ $session['device'] }} @if ($session['current'])<x-badge status="active" label="Cet appareil" class="ml-1" />@endif</p>
                        <p class="text-[12px] text-muted">IP {{ $session['ip'] }} · active {{ $session['last']->diffForHumans() }}</p>
                    </td>
                    <td class="text-right">
                        @unless ($session['current'])
                            <form method="POST" action="{{ route('security.revoke', $session['id']) }}">@csrf<button class="text-[12.5px] font-semibold text-alert hover:underline">Déconnecter</button></form>
                        @endunless
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="card mt-6 overflow-hidden">
    <div class="border-b border-line px-5 py-4">
        <h2 class="display text-[19px]">Journal d'authentification</h2>
        <p class="text-[12.5px] text-muted">{{ $isAdmin ? 'Connexions, échecs et alertes de toute l\'équipe.' : 'Vos 50 derniers événements.' }}</p>
    </div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Date</th><th>Événement</th>@if ($isAdmin)<th>Utilisateur</th>@endif<th>Adresse IP</th></tr></thead>
            <tbody>
                @forelse ($events as $event)
                    <tr>
                        <td class="whitespace-nowrap text-graphite">{{ $event->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            @php($bad = in_array($event->type, ['login_failed', 'locked', 'two_factor_failed', 'new_device'], true))
                            <span class="font-semibold {{ $bad ? 'text-alert' : '' }}">{{ $event->label() }}</span>
                        </td>
                        @if ($isAdmin)<td>{{ $event->user?->fullName() ?? '—' }}</td>@endif
                        <td class="font-mono text-[12px] text-muted">{{ $event->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-empty icon="shield" title="Aucun événement" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
