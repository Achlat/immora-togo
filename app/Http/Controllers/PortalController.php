<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Tenant;
use App\Services\Documents\PdfDocuments;
use App\Services\Payments\OnlinePaymentService;
use App\Support\CurrentAgency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

/**
 * Portails locataire et propriétaire (CDC chap. 18). Le cloisonnement est vérifié
 * côté serveur à chaque requête (PORT-03).
 */
final class PortalController extends Controller
{
    public function __construct(private readonly CurrentAgency $current) {}

    public function home(Request $request): View
    {
        return $this->current->role() === 'owner' ? $this->ownerHome($request) : $this->tenantHome($request);
    }

    public function pay(Request $request, OnlinePaymentService $online): RedirectResponse
    {
        $tenant = $this->tenant($request);
        $data = $request->validate(['invoice_id' => ['nullable', 'integer']]);
        $invoice = isset($data['invoice_id']) ? $tenant->invoices()->open()->find($data['invoice_id']) : null;
        $amount = $invoice?->balance() ?? $tenant->balanceDue();

        if ($amount <= 0) {
            return back()->with('status', 'Vous êtes à jour : rien à payer pour le moment.');
        }

        try {
            return redirect()->away($online->start($tenant, $invoice, $amount));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function invoicePdf(Request $request, Invoice $invoice, PdfDocuments $pdf): Response
    {
        abort_unless($invoice->tenant_id === $this->tenant($request)->id, 404);

        return $pdf->invoice($invoice);
    }

    public function paymentPdf(Request $request, Payment $payment, PdfDocuments $pdf): Response
    {
        abort_unless($payment->tenant_id === $this->tenant($request)->id, 404);

        return $pdf->payment($payment);
    }

    public function statementPdf(Request $request, OwnerStatement $statement, PdfDocuments $pdf): Response
    {
        abort_unless($statement->owner_id === $this->owner($request)->id && $statement->status !== 'draft', 404);

        return $pdf->statement($statement);
    }

    private function tenantHome(Request $request): View
    {
        $tenant = $this->tenant($request);
        $tenant->load(['leases' => fn ($q) => $q->whereIn('status', ['active', 'notice'])->with('unit.property')]);

        return view('portal.tenant', [
            'tenant' => $tenant,
            'due' => $tenant->balanceDue(),
            'openInvoices' => $tenant->invoices()->open()->orderBy('due_date')->get(),
            'receipts' => $tenant->invoices()->where('status', 'paid')->whereNotNull('receipt_number')->latest('paid_at')->limit(12)->get(),
            'payments' => $tenant->payments()->where('amount', '>', 0)->latest('paid_at')->limit(10)->get(),
            'credit' => $tenant->availableCredit(),
        ]);
    }

    private function ownerHome(Request $request): View
    {
        $owner = $this->owner($request);
        $owner->load(['properties.units.activeLease.tenant', 'mandate']);
        $monthStart = now()->startOfMonth();

        $collected = (int) PaymentAllocation::query()->where('allocated_at', '>=', $monthStart)
            ->whereHas('line', fn ($q) => $q->whereIn('type', ['rent', 'charges', 'penalty']))
            ->whereHas('line.invoice.lease.unit.property', fn ($q) => $q->where('owner_id', $owner->id))->sum('amount');

        $units = $owner->properties->flatMap->units;

        return view('portal.owner', [
            'owner' => $owner,
            'collected' => $collected,
            'expected' => (int) $units->sum(fn ($u) => $u->activeLease?->monthlyTotal() ?? 0),
            'occupied' => $units->where('status', 'occupied')->count(),
            'unitsCount' => $units->count(),
            'statements' => $owner->statements()->where('status', '!=', 'draft')->latest('period_start')->limit(12)->get(),
        ]);
    }

    private function tenant(Request $request): Tenant
    {
        return Tenant::query()->where('user_id', $request->user()->id)->firstOrFail();
    }

    private function owner(Request $request): Owner
    {
        return Owner::query()->where('user_id', $request->user()->id)->firstOrFail();
    }
}
