<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Services\Documents\PdfDocuments;
use App\Services\Messaging\SmsSender;
use App\Support\CurrentAgency;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** Échéances, avis et relances (RENT-03, RENT-05). */
final class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'open');
        $invoices = Invoice::query()->with(['tenant', 'lease.unit'])
            ->when($filter === 'open', fn ($q) => $q->open())
            ->when($filter === 'overdue', fn ($q) => $q->open()->where('due_date', '<', now()->toDateString()))
            ->when($filter === 'paid', fn ($q) => $q->where('status', 'paid'))
            ->when($request->query('month'), fn ($q, $month) => $q->where('period_start', 'like', $month.'%'))
            ->orderBy($filter === 'paid' ? 'paid_at' : 'due_date', $filter === 'paid' ? 'desc' : 'asc')
            ->paginate(30)->withQueryString();

        return view('invoices.index', ['invoices' => $invoices, 'filter' => $filter]);
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['tenant', 'lease.unit.property', 'lines.allocations.payment']);

        return view('invoices.show', ['invoice' => $invoice]);
    }

    public function pdf(Invoice $invoice, PdfDocuments $pdf): Response
    {
        return $pdf->invoice($invoice);
    }

    public function remind(Invoice $invoice, SmsSender $sms, CurrentAgency $current): RedirectResponse
    {
        $tenant = $invoice->tenant;
        $phone = $tenant->whatsapp_e164 ?: $tenant->phone_e164;
        if ($phone === null || $invoice->balance() <= 0) {
            return back()->with('error', 'Relance impossible : pas de numéro ou rien à payer.');
        }

        $agency = $current->get();
        $body = "Bonjour {$tenant->greetingName()}, sauf erreur de notre part, il reste ".Money::format($invoice->balance())
            .' à régler pour '.mb_strtolower($invoice->periodLabel()).' (échéance du '.$invoice->due_date->format('d/m').'). '
            ."Paiement Mobile Money possible depuis votre espace en ligne. — {$agency->name}, {$agency->phone}";
        $sms->send($phone, $body, 'reminder', $agency->id);
        AuditLog::record('invoice.reminded', $invoice);

        return back()->with('success', 'Relance envoyée par SMS à '.$tenant->displayName().'.');
    }
}
