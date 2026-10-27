@extends('layouts.public')
@section('title', 'Paiement Mobile Money')

@section('content')
<div class="mb-5 rounded-[10px] border border-gold/40 bg-gold-wash px-4 py-3 text-[13px] font-semibold text-gold-deep">
    Mode démonstration : ce simulateur remplace la page de l'agrégateur (CinetPay) tant que les clés marchandes ne sont pas configurées.
</div>
<form method="POST" action="{{ route('payments.simulate', $intent->reference) }}" class="card p-6" x-data="{ op: 'wave' }">
    @csrf
    <p class="eyebrow">{{ $intent->agency->name }}</p>
    <h1 class="display mt-1 text-[28px]">Payer <x-money :amount="$intent->amount + $intent->fee_amount" /></h1>
    <p class="mt-1 text-[13.5px] text-muted">Référence {{ $intent->reference }}</p>

    <div class="mt-6 grid grid-cols-2 gap-2">
        @foreach (config('immora.mobile_money_providers') as $value => $label)
            <label class="cursor-pointer rounded-[10px] border px-3 py-3 text-center text-[13.5px] font-semibold"
                :class="op === '{{ $value }}' ? 'border-ink bg-ink text-white' : 'border-line-strong'">
                <input type="radio" name="operator" value="{{ $value }}" x-model="op" class="sr-only">{{ $label }}
            </label>
        @endforeach
    </div>
    <div class="mt-6 grid grid-cols-2 gap-3">
        <button name="decision" value="refused" class="btn btn-ghost h-12">Refuser</button>
        <button name="decision" value="accepted" class="btn btn-gold h-12">Confirmer le paiement</button>
    </div>
</form>
@endsection
