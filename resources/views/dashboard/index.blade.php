@extends('layouts.app')
@section('title', 'Tableau de bord')

@section('content')
<header class="mb-10 flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
    <div>
        <p class="eyebrow !text-gold-deep">{{ ucfirst(now()->translatedFormat('l j F Y')) }}</p>
        <h1 class="display mt-2 text-[38px] leading-[1.08]">{{ $greeting }}, {{ auth()->user()->first_name }}.</h1>
        <p class="mt-2 text-[14.5px] text-muted">
            @if ($todo['overdue'] > 0)
                {{ $todo['overdue'] }} échéance{{ $todo['overdue'] > 1 ? 's' : '' }} en retard demandent votre attention.
            @else
                Aucun retard de paiement aujourd'hui. Belle journée.
            @endif
        </p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('reports.arrears') }}" class="btn btn-ghost">Voir les impayés</a>
        <a href="{{ route('statements.index') }}" class="btn btn-primary">Relevés du mois</a>
    </div>
</header>

{{-- Indicateurs clés (DASH-01) --}}
<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="card relative overflow-hidden bg-ink p-5 text-white">
        <p class="eyebrow !text-gold-light/90">Encaissé ce mois</p>
        <p class="display num mt-3 text-[32px] leading-none text-white">{{ number_format($collected, 0, ',', "\u{202F}") }}</p>
        <p class="mt-1 text-[12px] font-bold text-gold-light">FCFA</p>
        <div class="mt-4 h-1 rounded-full bg-white/10">
            <div class="h-1 rounded-full bg-gold-light" style="width: {{ $expected > 0 ? min(100, round($collected / $expected * 100)) : 0 }}%"></div>
        </div>
        <p class="mt-2 text-[12px] text-[#a9a59b]">Attendu : {{ number_format($expected, 0, ',', "\u{202F}") }} FCFA</p>
    </div>
    <x-stat label="Taux de recouvrement" :value="$recoveryRate !== null ? number_format($recoveryRate, 1, ',', '') : '—'" unit="%"
        note="Échéances du mois réglées" />
    <x-stat label="Impayés" :value="number_format($arrearsTotal, 0, ',', ' ')" unit="FCFA" :tone="$arrearsTotal > 0 ? 'alert' : 'default'"
        :note="$arrearsTenants.' locataire'.($arrearsTenants > 1 ? 's' : '').' concerné'.($arrearsTenants > 1 ? 's' : '')" />
    <x-stat label="Taux d'occupation" :value="number_format($occupancy, 1, ',', '')" unit="%"
        :note="$unitsOccupied.' lots occupés sur '.$unitsTotal" />
</section>

<section class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
    {{-- Graphique 12 mois (DASH-03) --}}
    <div class="card p-6">
        <div class="flex flex-wrap items-baseline justify-between gap-3">
            <div>
                <h2 class="display text-[21px]">Encaissements sur 12 mois</h2>
                <p class="text-[13px] text-muted">Comparés au montant appelé chaque mois.</p>
            </div>
            <div class="flex gap-4 text-[12px] font-semibold text-graphite">
                <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-ink"></span>Encaissé</span>
                <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-sm border border-line-strong bg-ivory"></span>Appelé</span>
            </div>
        </div>
        @php
            $max = max(1, collect($chart)->flatMap(fn ($r) => [$r['collected'], $r['expected']])->max());
            $h = 200; $w = 720; $slot = $w / count($chart); $bw = min(22, $slot * 0.32);
        @endphp
        <svg viewBox="0 0 {{ $w }} {{ $h + 28 }}" class="mt-6 w-full" role="img" aria-label="Encaissements mensuels sur 12 mois">
            @foreach ([0.25, 0.5, 0.75, 1] as $t)
                <line x1="0" x2="{{ $w }}" y1="{{ $h - $h * $t }}" y2="{{ $h - $h * $t }}" stroke="#EFEBE2" stroke-width="1"/>
            @endforeach
            @foreach ($chart as $i => $row)
                @php
                    $x = $i * $slot + $slot / 2;
                    $he = $row['expected'] / $max * ($h - 8);
                    $hc = $row['collected'] / $max * ($h - 8);
                    $last = $i === count($chart) - 1;
                @endphp
                <g>
                    <title>{{ $row['label'] }} — encaissé {{ number_format($row['collected'], 0, ',', ' ') }} FCFA / appelé {{ number_format($row['expected'], 0, ',', ' ') }} FCFA</title>
                    <rect x="{{ $x - $bw - 1 }}" y="{{ $h - $he }}" width="{{ $bw }}" height="{{ $he }}" rx="2" fill="#F4F1EA" stroke="#D6D0C3"/>
                    <rect x="{{ $x + 1 }}" y="{{ $h - $hc }}" width="{{ $bw }}" height="{{ $hc }}" rx="2" fill="{{ $last ? '#B8913E' : '#0D0D0E' }}"/>
                    <text x="{{ $x }}" y="{{ $h + 20 }}" text-anchor="middle" font-size="11" font-weight="{{ $last ? 700 : 500 }}" fill="{{ $last ? '#0D0D0E' : '#6F6C64' }}">{{ $row['label'] }}</text>
                </g>
            @endforeach
        </svg>
    </div>

    {{-- À faire aujourd'hui (DASH-02) --}}
    <div class="card flex flex-col">
        <div class="border-b border-line px-5 py-4">
            <h2 class="display text-[19px]">À faire aujourd'hui</h2>
        </div>
        <ul class="flex-1 divide-y divide-line">
            @foreach ([
                ['overdue', 'Relances à envoyer', route('invoices.index', ['filter' => 'overdue']), 'send'],
                ['renewals', 'Baux à renouveler sous 60 jours', route('leases.index'), 'calendar'],
                ['statements', 'Relevés à valider', route('statements.index'), 'statement'],
                ['drafts', 'Baux en brouillon à activer', route('leases.index', ['status' => 'draft']), 'key'],
            ] as [$key, $label, $url, $icon])
                <li>
                    <a href="{{ $url }}" class="group flex items-center gap-3 px-5 py-3.5 hover:bg-paper">
                        <x-icon :name="$icon" class="size-4 text-faint group-hover:text-gold-deep" />
                        <span class="flex-1 text-[13.5px] font-medium">{{ $label }}</span>
                        <span class="num min-w-7 rounded-full px-2 py-0.5 text-center text-[12px] font-bold {{ $todo[$key] > 0 ? 'bg-ink text-gold-light' : 'bg-ivory text-faint' }}">{{ $todo[$key] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="border-t border-line p-5">
            <p class="eyebrow mb-3">Impayés par ancienneté</p>
            @php($bmax = max(1, max($buckets)))
            <div class="space-y-2.5">
                @foreach ($buckets as $label => $amount)
                    <div class="grid grid-cols-[64px_1fr_auto] items-center gap-3 text-[12.5px]">
                        <span class="font-semibold text-graphite">{{ $label }}</span>
                        <span class="h-1.5 rounded-full bg-ivory"><span class="block h-1.5 rounded-full {{ $loop->last ? 'bg-alert' : 'bg-ink' }}" style="width: {{ round($amount / $bmax * 100) }}%"></span></span>
                        <span class="num text-right font-semibold">{{ number_format($amount, 0, ',', "\u{202F}") }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<section class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="card">
        <div class="flex items-center justify-between border-b border-line px-5 py-4">
            <h2 class="display text-[19px]">Derniers encaissements</h2>
            <a href="{{ route('payments.index') }}" class="link text-[12.5px]">Tout voir</a>
        </div>
        <table class="table">
            <tbody>
                @forelse ($latestPayments as $payment)
                    <tr class="row-link" data-href="{{ route('payments.show', $payment) }}">
                        <td>
                            <p class="font-semibold">{{ $payment->tenant->displayName() }}</p>
                            <p class="text-[12px] text-muted">{{ $payment->paid_at->translatedFormat('j M, H\hi') }} · {{ $payment->methodLabel() }}</p>
                        </td>
                        <td class="amount {{ $payment->amount < 0 ? 'text-alert' : '' }}"><x-money :amount="$payment->amount" class="font-semibold" /></td>
                    </tr>
                @empty
                    <tr><td><x-empty icon="receipt" title="Aucun encaissement" text="Les paiements apparaîtront ici." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="flex items-center justify-between border-b border-line px-5 py-4">
            <h2 class="display text-[19px]">Plus gros retards</h2>
            <a href="{{ route('reports.arrears') }}" class="link text-[12.5px]">État complet</a>
        </div>
        <table class="table">
            <tbody>
                @forelse ($topArrears as $row)
                    <tr class="row-link" data-href="{{ route('tenants.show', $row->tenant_id) }}">
                        <td>
                            <p class="font-semibold">{{ $row->tenant->displayName() }}</p>
                            <p class="text-[12px] text-muted">Depuis le {{ \Illuminate\Support\Carbon::parse($row->oldest)->format('d/m/Y') }}</p>
                        </td>
                        <td class="amount text-alert"><x-money :amount="$row->due" class="font-semibold" /></td>
                    </tr>
                @empty
                    <tr><td><x-empty icon="check" title="Aucun retard" text="Tous les locataires sont à jour." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
