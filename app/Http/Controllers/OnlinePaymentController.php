<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PaymentIntent;
use App\Services\Payments\OnlinePaymentService;
use App\Services\Payments\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Retour et notifications des paiements en ligne (PAY-01 à PAY-03).
 */
final class OnlinePaymentController extends Controller
{
    public function __construct(
        private readonly OnlinePaymentService $online,
        private readonly PaymentGateway $gateway,
    ) {}

    /** Webhook de l'agrégateur : signature vérifiée puis statut re-contrôlé par appel API. */
    public function notify(Request $request, string $provider): Response
    {
        if ($provider !== $this->gateway->name()) {
            return response('Fournisseur inconnu', 404);
        }

        $payload = $request->all();
        if (! $this->gateway->verifyNotification($payload, $request->header('x-token'))) {
            Log::warning('Notification de paiement à la signature invalide', ['provider' => $provider]);

            return response('Signature invalide', 401);
        }

        $reference = (string) ($payload['cpm_trans_id'] ?? $payload['reference'] ?? '');
        $intent = PaymentIntent::query()->where('reference', $reference)->first();
        if ($intent !== null) {
            $this->online->confirm($intent);
        }

        return response('OK', 200);
    }

    public function return(string $reference): View
    {
        $intent = PaymentIntent::query()->where('reference', $reference)->with('agency')->firstOrFail();
        $payment = $this->online->confirm($intent);

        return view('public.payment-return', ['intent' => $intent->refresh(), 'payment' => $payment]);
    }

    public function simulator(string $reference): View
    {
        abort_unless($this->gateway->name() === 'fake', 404);
        $intent = PaymentIntent::query()->where('reference', $reference)->where('status', 'pending')->with(['agency', 'tenant'])->firstOrFail();

        return view('public.simulator', ['intent' => $intent]);
    }

    public function simulate(Request $request, string $reference): RedirectResponse
    {
        abort_unless($this->gateway->name() === 'fake', 404);
        $intent = PaymentIntent::query()->where('reference', $reference)->where('status', 'pending')->firstOrFail();
        $data = $request->validate([
            'decision' => ['required', Rule::in(['accepted', 'refused'])],
            'operator' => ['required', Rule::in(array_keys(config('immora.mobile_money_providers')))],
        ]);

        $intent->update(['provider_payload' => [
            'simulated_status' => $data['decision'],
            'simulated_operator' => $data['operator'],
            'simulated_ref' => strtoupper($data['operator']).'-'.Str::upper(Str::random(10)),
        ]]);

        return redirect()->route('payments.return', $reference);
    }
}
