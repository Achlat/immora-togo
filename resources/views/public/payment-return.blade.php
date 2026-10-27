@extends('layouts.public')
@section('title', 'Paiement')

@section('content')
<div class="card p-8 text-center">
    @if ($payment)
        <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-ok-wash text-ok"><x-icon name="check" class="size-6" /></span>
        <h1 class="display mt-5 text-[28px]">Paiement reçu, merci !</h1>
        <p class="mt-2 text-[14px] text-muted"><x-money :amount="$payment->amount" class="font-bold text-ink" /> ont bien été versés à {{ $intent->agency->name }}. Votre reçu vous est envoyé par SMS.</p>
        <a href="{{ route('verify.show', $payment->verify_code) }}" class="btn btn-ghost mt-6">Voir le reçu</a>
    @elseif ($intent->status === 'failed')
        <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-alert-wash text-alert"><x-icon name="x" class="size-6" /></span>
        <h1 class="display mt-5 text-[28px]">Paiement non abouti</h1>
        <p class="mt-2 text-[14px] text-muted">Aucun montant n'a été débité. Vous pouvez réessayer depuis votre espace.</p>
    @else
        <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-warn-wash text-warn"><x-icon name="info" class="size-6" /></span>
        <h1 class="display mt-5 text-[28px]">Paiement en cours de confirmation</h1>
        <p class="mt-2 text-[14px] text-muted">L'opérateur n'a pas encore confirmé. Votre reçu arrivera par SMS dès validation, sans action de votre part.</p>
    @endif
    @auth<a href="{{ route('portal.home') }}" class="btn btn-primary mt-6 ml-2">Retour à mon espace</a>@endauth
</div>
@endsection
