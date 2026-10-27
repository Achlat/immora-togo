<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\Owner;
use App\Models\Property;
use App\Support\CurrentAgency;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Dépenses et justificatifs (ACC-04). */
final class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $month = $request->query('month', now()->format('Y-m'));
        $expenses = Expense::query()->with(['owner', 'property', 'unit'])
            ->where('spent_on', 'like', $month.'%')->latest('spent_on')->paginate(30)->withQueryString();

        return view('expenses.index', [
            'expenses' => $expenses,
            'month' => $month,
            'total' => (int) Expense::query()->where('spent_on', 'like', $month.'%')->sum('amount'),
        ]);
    }

    public function create(): View
    {
        return view('expenses.create', [
            'properties' => Property::query()->with(['owner', 'units'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, CurrentAgency $current): RedirectResponse
    {
        $request->merge(['amount' => Money::parse($request->input('amount'))]);
        $agencyId = $current->id();
        $data = $request->validate([
            'property_id' => ['nullable', Rule::exists('properties', 'id')->where('agency_id', $agencyId)],
            'unit_id' => ['nullable', Rule::exists('units', 'id')->where('agency_id', $agencyId)],
            'charged_to' => ['required', Rule::in(array_keys(Expense::CHARGED_TO))],
            'category' => ['required', Rule::in(array_keys(config('immora.expense_categories')))],
            'label' => ['required', 'string', 'max:190'],
            'amount' => ['required', 'integer', 'min:1'],
            'spent_on' => ['required', 'date', 'before_or_equal:today'],
            'supplier' => ['nullable', 'string', 'max:150'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $property = isset($data['property_id']) ? Property::query()->find($data['property_id']) : null;
        if ($data['charged_to'] === 'owner' && $property?->owner_id === null) {
            return back()->withInput()->with('error', 'Choisissez un bien rattaché à un propriétaire pour lui imputer cette dépense.');
        }

        $path = $request->file('receipt')?->store("agencies/{$agencyId}/receipts", 'local');
        $ceiling = $property?->owner?->mandate?->expense_ceiling_amount;

        $expense = Expense::create(collect($data)->except('receipt')->all() + [
            'owner_id' => $data['charged_to'] === 'owner' ? $property?->owner_id : null,
            'receipt_path' => $path,
            'status' => 'approved',
            'created_by' => $request->user()->id,
        ]);
        AuditLog::record('expense.created', $expense, ['amount' => $expense->amount]);

        $message = 'Dépense enregistrée.';
        if ($ceiling !== null && $expense->amount > $ceiling) {
            $message .= ' Attention : elle dépasse le plafond du mandat ('.Money::format($ceiling).'), pensez à obtenir l\'accord écrit du propriétaire.';
        }

        return redirect()->route('expenses.index', ['month' => $expense->spent_on->format('Y-m')])->with('success', $message);
    }
}
