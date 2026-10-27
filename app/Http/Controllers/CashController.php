<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CashSession;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Caisse espèces : ouverture, clôture avec billetage et écart justifié (RENT-07). */
final class CashController extends Controller
{
    public const DENOMINATIONS = [10000, 5000, 2000, 1000, 500, 250, 200, 100, 50, 25, 10, 5];

    public function index(Request $request): View
    {
        $open = CashSession::query()->where('user_id', $request->user()->id)->whereNull('closed_at')->with('payments.tenant')->first();

        return view('cash.index', [
            'open' => $open,
            'history' => CashSession::query()->with('user')->whereNotNull('closed_at')->latest('closed_at')->limit(15)->get(),
            'denominations' => self::DENOMINATIONS,
        ]);
    }

    public function open(Request $request): RedirectResponse
    {
        $request->merge(['opening_amount' => Money::parse($request->input('opening_amount'))]);
        $data = $request->validate(['opening_amount' => ['required', 'integer', 'min:0']]);

        if (CashSession::query()->where('user_id', $request->user()->id)->whereNull('closed_at')->exists()) {
            return back()->with('error', 'Votre caisse est déjà ouverte.');
        }

        $session = CashSession::create($data + ['user_id' => $request->user()->id, 'opened_at' => now()]);
        AuditLog::record('cash.opened', $session, $data);

        return back()->with('success', 'Caisse ouverte avec un fonds de '.Money::format($data['opening_amount']).'.');
    }

    public function close(Request $request, CashSession $session): RedirectResponse
    {
        abort_unless($session->isOpen(), 422);

        $counts = collect(self::DENOMINATIONS)->mapWithKeys(fn (int $value) => [$value => max(0, (int) $request->input("d.{$value}", 0))]);
        $counted = $counts->reduce(fn (int $sum, int $qty, int $value) => $sum + $qty * $value, 0);
        $expected = $session->expectedAmount();

        if ($counted !== $expected && trim((string) $request->input('gap_reason')) === '') {
            return back()->withInput()->with('error', 'Écart de '.Money::format($counted - $expected).' : la justification est obligatoire.');
        }

        $session->update([
            'expected_amount' => $expected,
            'counted_amount' => $counted,
            'denominations' => $counts->filter()->all(),
            'gap_reason' => $request->input('gap_reason'),
            'closed_at' => now(),
        ]);
        AuditLog::record('cash.closed', $session, ['expected' => $expected, 'counted' => $counted]);

        $message = $counted === $expected ? 'Caisse clôturée sans écart.' : 'Caisse clôturée avec un écart de '.Money::format($counted - $expected).', justifié.';

        return redirect()->route('cash.index')->with('success', $message);
    }
}
