<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\View\View;

/**
 * Vérification publique d'une quittance ou d'un reçu via son QR code (RENT-04).
 * Aucune donnée personnelle n'est affichée : seulement les initiales du locataire.
 */
final class VerifyController extends Controller
{
    public function show(string $code): View
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
        $invoice = Invoice::withoutGlobalScopes()->with(['agency', 'tenant' => fn ($q) => $q->withoutGlobalScopes()])->where('verify_code', $code)->first();
        $payment = $invoice === null
            ? Payment::withoutGlobalScopes()->with(['agency', 'tenant' => fn ($q) => $q->withoutGlobalScopes()])->where('verify_code', $code)->first()
            : null;

        if ($invoice !== null) {
            return view('public.verify', [
                'found' => true,
                'valid' => $invoice->status === 'paid',
                'type' => 'Quittance de loyer',
                'number' => $invoice->receipt_number,
                'amount' => $invoice->total_amount,
                'date' => $invoice->paid_at,
                'period' => $invoice->periodLabel(),
                'agency' => $invoice->agency->name,
                'initials' => $this->initials($invoice->tenant?->displayName()),
            ]);
        }

        if ($payment !== null) {
            return view('public.verify', [
                'found' => true,
                'valid' => $payment->status === 'confirmed',
                'type' => 'Reçu de paiement',
                'number' => $payment->number,
                'amount' => $payment->amount,
                'date' => $payment->paid_at,
                'period' => null,
                'agency' => $payment->agency->name,
                'initials' => $this->initials($payment->tenant?->displayName()),
            ]);
        }

        return view('public.verify', ['found' => false]);
    }

    private function initials(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];

        return implode('. ', array_map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_filter($parts))).'.';
    }
}
