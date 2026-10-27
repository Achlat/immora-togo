<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Services\Billing\PaymentService;
use App\Services\Documents\PdfDocuments;
use App\Services\Messaging\Notifier;
use App\Support\CurrentAgency;
use App\Support\Money;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Encaissements (CDC chap. 13). Parcours cible : moins de 2 minutes du locataire à la quittance.
 */
final class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function index(Request $request): View
    {
        $payments = Payment::query()->with(['tenant', 'creator'])
            ->when($request->query('method'), fn ($q, $m) => $q->where('method', $m))
            ->when($request->query('from'), fn ($q, $d) => $q->whereDate('paid_at', '>=', $d))
            ->when($request->query('to'), fn ($q, $d) => $q->whereDate('paid_at', '<=', $d))
            ->latest('paid_at')->paginate(30)->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'todayTotal' => (int) Payment::query()->whereDate('paid_at', today())->sum('amount'),
        ]);
    }

    public function create(Request $request): View
    {
        $tenant = $request->query('tenant') ? Tenant::query()->find($request->query('tenant')) : null;

        return view('payments.create', [
            'tenant' => $tenant,
            'openInvoices' => $tenant?->invoices()->open()->orderBy('due_date')->get() ?? collect(),
            'balance' => $tenant?->balanceDue() ?? 0,
            'tenants' => $tenant ? collect() : Tenant::query()->whereHas('leases', fn ($q) => $q->whereIn('status', ['active', 'notice']))
                ->orderBy('last_name')->get(),
            'cashSession' => CashSession::query()->where('user_id', $request->user()->id)->whereNull('closed_at')->first(),
        ]);
    }

    public function store(Request $request, Notifier $notifier, CurrentAgency $current): RedirectResponse
    {
        $request->merge(['amount' => Money::parse($request->input('amount'))]);
        $data = $request->validate([
            'tenant_id' => ['required', Rule::exists('tenants', 'id')->where('agency_id', $current->id())],
            'amount' => ['required', 'integer', 'min:100', 'max:1000000000'],
            'method' => ['required', Rule::in(array_keys(config('immora.payment_methods')))],
            'provider' => ['nullable', 'required_if:method,mobile_money', Rule::in(array_keys(config('immora.mobile_money_providers')))],
            'external_ref' => ['nullable', 'required_if:method,mobile_money', 'string', 'max:100'],
            'reference_note' => ['nullable', 'string', 'max:190'],
            'paid_at' => ['required', 'date', 'before_or_equal:now'],
        ], [
            'external_ref.required_if' => 'Saisissez la référence de la transaction Mobile Money (elle évite les doublons).',
            'provider.required_if' => 'Choisissez l\'opérateur Mobile Money.',
        ]);

        $cashSessionId = null;
        if ($data['method'] === 'cash') {
            $session = CashSession::query()->where('user_id', $request->user()->id)->whereNull('closed_at')->first();
            if ($session === null) {
                return back()->withInput()->with('error', 'Ouvrez votre caisse avant d\'encaisser des espèces.');
            }
            $cashSessionId = $session->id;
        }

        $tenant = Tenant::query()->findOrFail($data['tenant_id']);
        if ($data['method'] === 'mobile_money' && Payment::query()->where('provider', $data['provider'])->where('external_ref', $data['external_ref'])->exists()) {
            return back()->withInput()->with('error', 'Cette transaction Mobile Money est déjà enregistrée.');
        }

        try {
            $payment = $this->payments->record($tenant, $data['amount'], $data['method'], [
                'provider' => $data['provider'] ?? null,
                'external_ref' => $data['external_ref'] ?? null,
                'reference_note' => $data['reference_note'] ?? null,
                'paid_at' => $data['paid_at'],
                'cash_session_id' => $cashSessionId,
            ]);
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $firstInvoice = Invoice::query()->whereIn('id', $payment->allocations()->with('line')->get()->pluck('line.invoice_id'))->orderBy('due_date')->first();
        $notifier->paymentReceived($payment, $firstInvoice, $current->get(), route('verify.show', $payment->verify_code));

        return redirect()->route('payments.show', $payment)
            ->with('success', 'Encaissement de '.Money::format($payment->amount).' enregistré. Reçu envoyé au locataire par SMS.');
    }

    public function show(Payment $payment): View
    {
        $payment->load(['tenant', 'creator', 'allocations.line.invoice', 'reversal']);
        $invoices = $payment->allocations->pluck('line.invoice')->unique('id')->values();

        return view('payments.show', ['payment' => $payment, 'invoices' => $invoices]);
    }

    public function pdf(Payment $payment, PdfDocuments $pdf): Response
    {
        return $pdf->payment($payment);
    }

    public function reverse(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:255']], [
            'reason.required' => 'Le motif d\'annulation est obligatoire.',
            'reason.min' => 'Précisez le motif (10 caractères minimum).',
        ]);

        try {
            $reversal = $this->payments->reverse($payment, $data['reason']);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Encaissement annulé par la contre-écriture {$reversal->number}. Les échéances concernées sont de nouveau dues.");
    }
}
