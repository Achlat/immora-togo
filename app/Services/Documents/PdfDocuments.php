<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Models\Invoice;
use App\Models\OwnerStatement;
use App\Models\Payment;
use App\Support\FrenchNumber;
use App\Support\Qr;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Documents PDF : quittance, reçu de paiement, avis d'échéance et relevé de gérance.
 * Chaque quittance et reçu porte un QR code de vérification (RENT-04).
 */
final class PdfDocuments
{
    public function invoice(Invoice $invoice): Response
    {
        $invoice->load('lines', 'lease.unit.property', 'tenant', 'agency');
        $isReceipt = $invoice->status === 'paid' && $invoice->receipt_number !== null;

        return $this->render('pdf.invoice', [
            'invoice' => $invoice,
            'agency' => $invoice->agency,
            'isReceipt' => $isReceipt,
            'qr' => $isReceipt ? Qr::pngDataUri(route('verify.show', $invoice->verify_code)) : null,
            'words' => FrenchNumber::amountInWords($isReceipt ? $invoice->total_amount : $invoice->balance()),
        ], ($isReceipt ? $invoice->receipt_number : $invoice->number).'.pdf');
    }

    public function payment(Payment $payment): Response
    {
        $payment->load('tenant', 'allocations.line.invoice', 'agency', 'creator');

        return $this->render('pdf.payment', [
            'payment' => $payment,
            'agency' => $payment->agency,
            'qr' => Qr::pngDataUri(route('verify.show', $payment->verify_code)),
            'words' => FrenchNumber::amountInWords(abs($payment->amount)),
        ], $payment->number.'.pdf');
    }

    public function statement(OwnerStatement $statement): Response
    {
        $statement->load('owner', 'agency');

        return $this->render('pdf.statement', [
            'statement' => $statement,
            'agency' => $statement->agency,
            'words' => FrenchNumber::amountInWords($statement->payout_amount),
        ], $statement->number.'.pdf');
    }

    private function render(string $view, array $data, string $filename): Response
    {
        return Pdf::loadView($view, $data)->setPaper('a4')->stream($filename);
    }
}
