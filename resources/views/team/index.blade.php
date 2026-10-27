@extends('layouts.app')
@section('title', 'Équipe')

@section('content')
<x-page-header title="Équipe" kicker="Agence">
    <x-slot:subtitle>Les collaborateurs rejoignent l'agence uniquement sur invitation. Désactiver un compte coupe ses accès immédiatement.</x-slot:subtitle>
</x-page-header>

<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
    <div class="space-y-6">
        <div class="card overflow-hidden">
            <table class="table">
                <thead><tr><th>Collaborateur</th><th>Rôle</th><th>Double auth.</th><th>Dernière connexion</th><th></th></tr></thead>
                <tbody>
                    @foreach ($members as $member)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="flex size-9 items-center justify-center rounded-full bg-ink text-[12px] font-bold text-gold-light">{{ $member->initials() }}</span>
                                    <div><p class="font-semibold">{{ $member->fullName() }}</p><p class="text-[12px] text-muted">{{ $member->email ?? \App\Support\Phone::display($member->phone_e164) }}</p></div>
                                </div>
                            </td>
                            <td>{{ $roles[$member->pivot->role] ?? $member->pivot->role }}</td>
                            <td>@if ($member->hasTwoFactor())<x-badge status="active" label="Activée" />@else<x-badge status="draft" label="Non activée" />@endif</td>
                            <td class="text-graphite">{{ $member->last_login_at?->diffForHumans() ?? 'Jamais' }}</td>
                            <td class="text-right">
                                @if ($member->id !== auth()->id())
                                    <form method="POST" action="{{ route('team.toggle', $member) }}"
                                        data-confirm="{{ $member->pivot->is_active ? 'Révoquer immédiatement l\'accès de '.$member->fullName().' ?' : 'Rétablir l\'accès de '.$member->fullName().' ?' }}">
                                        @csrf
                                        <button class="text-[12.5px] font-semibold {{ $member->pivot->is_active ? 'text-alert' : 'text-ok' }} hover:underline">{{ $member->pivot->is_active ? 'Désactiver' : 'Réactiver' }}</button>
                                    </form>
                                @else
                                    <span class="text-[12px] text-faint">Vous</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($invitations->isNotEmpty())
            <div class="card overflow-hidden">
                <div class="border-b border-line px-5 py-4"><h2 class="display text-[19px]">Invitations en attente</h2></div>
                <table class="table">
                    <tbody>
                        @foreach ($invitations as $invitation)
                            <tr>
                                <td class="font-semibold">{{ $invitation->first_name }} {{ $invitation->last_name }}</td>
                                <td>{{ $roles[$invitation->role] ?? $invitation->role }}</td>
                                <td class="text-right text-[12.5px] text-muted">Expire {{ $invitation->expires_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('team.invite') }}" class="card h-fit space-y-4 p-5">
        @csrf
        <h2 class="display text-[20px]">Inviter un collaborateur</h2>
        <div class="grid grid-cols-2 gap-3">
            <x-field name="first_name" label="Prénom" required />
            <x-field name="last_name" label="Nom" required />
        </div>
        <x-field name="email" type="email" label="E-mail" hint="Ou laissez vide et indiquez un téléphone." />
        <x-field name="phone" type="tel" label="Téléphone" placeholder="+228 90 12 34 56" />
        <x-field name="role" label="Rôle" :options="$roles" value="manager" required />
        <button class="btn btn-primary w-full"><x-icon name="send" class="size-4" /> Envoyer l'invitation</button>
    </form>
</div>
@endsection
