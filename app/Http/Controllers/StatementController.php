<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Services\Billing\StatementService;
use App\Services\Documents\PdfDocuments;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Relevés de gérance et reversements (ACC-01 à ACC-03). */
final class StatementController extends Controller
{
    public function __construct(private readonly StatementService $statements) {}

    public function index(Request $request): View
    {
        $month = Carbon::createFromFormat('Y-m', $request->query('month', now()->subMonth()->format('Y-m')))->startOfMonth();
        $statements = OwnerStatement::query()->with('owner')->whereDate('period_start', $month)->get()->keyBy('owner_id');

        return view('statements.index', [
            'month' => $month,
            'owners' => Owner::query()->whereHas('mandate')->orderBy('last_name')->get(),
            'statements' => $statements,
            'totals' => [
                'collected' => $statements->sum('collected_amount'),
                'commission' => $statements->sum('commission_amount'),
                'payout' => $statements->sum('payout_amount'),
            ],
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $data = $request->validate(['month' => ['required', 'date_format:Y-m'], 'owner_id' => ['nullable', 'integer']]);
        $month = Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();

        $owners = Owner::query()->whereHas('mandate')->when($data['owner_id'] ?? null, fn ($q, $id) => $q->whereKey($id))->get();
        $done = 0;
        $skipped = 0;
        foreach ($owners as $owner) {
            try {
                $this->statements->generate($owner, $month, $request->user());
                $done++;
            } catch (DomainException) {
                $skipped++;
            }
        }

        $message = "{$done} relevé(s) généré(s) pour ".$month->translatedFormat('F Y').'.';
        if ($skipped > 0) {
            $message .= " {$skipped} relevé(s) déjà validé(s) n'ont pas été modifiés.";
        }

        return redirect()->route('statements.index', ['month' => $data['month']])->with('success', $message);
    }

    public function show(OwnerStatement $statement): View
    {
        return view('statements.show', ['statement' => $statement->load('owner.mandate', 'payout', 'creator')]);
    }

    public function pdf(OwnerStatement $statement, PdfDocuments $pdf): Response
    {
        return $pdf->statement($statement);
    }

    public function validateStatement(Request $request, OwnerStatement $statement): RedirectResponse
    {
        try {
            $this->statements->validate($statement, $request->user());
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Relevé validé. Vous pouvez enregistrer le reversement.');
    }

    public function payout(Request $request, OwnerStatement $statement): RedirectResponse
    {
        $data = $request->validate([
            'method' => ['required', Rule::in(['transfer', 'mobile_money', 'cash', 'cheque'])],
            'reference_note' => ['nullable', 'string', 'max:190'],
        ]);

        try {
            $this->statements->recordPayout($statement, $data['method'], $data['reference_note'] ?? null);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Reversement enregistré. Le relevé est disponible sur l\'espace du propriétaire.');
    }
}
