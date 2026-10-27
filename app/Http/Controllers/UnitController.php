<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Property;
use App\Models\Unit;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Lots, avec création en masse (PROP-02). */
final class UnitController extends Controller
{
    public function store(Request $request, Property $property): RedirectResponse
    {
        $request->merge([
            'base_rent_amount' => Money::parse($request->input('base_rent_amount')),
            'charges_amount' => Money::parse($request->input('charges_amount')),
        ]);
        $data = $request->validate($this->rules() + [
            'count' => ['required', 'integer', 'between:1,60'],
            'label_prefix' => ['required', 'string', 'max:20'],
            'start_number' => ['required', 'integer', 'between:1,999'],
        ]);

        $created = DB::transaction(function () use ($data, $property): int {
            for ($i = 0; $i < $data['count']; $i++) {
                $label = $data['label_prefix'].str_pad((string) ($data['start_number'] + $i), 2, '0', STR_PAD_LEFT);
                $reference = $property->reference.'-'.$label;
                if (Unit::query()->where('reference', $reference)->exists()) {
                    continue;
                }
                Unit::create(collect($data)->except(['count', 'label_prefix', 'start_number'])->all() + [
                    'property_id' => $property->id, 'label' => $label, 'reference' => $reference, 'status' => 'vacant',
                ]);
            }

            return $data['count'];
        });

        AuditLog::record('units.created', $property, ['count' => $created]);

        return back()->with('success', $created > 1 ? "{$created} lots créés." : 'Lot créé.');
    }

    public function edit(Unit $unit): View
    {
        return view('units.edit', ['unit' => $unit->load('property')]);
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $request->merge([
            'base_rent_amount' => Money::parse($request->input('base_rent_amount')),
            'charges_amount' => Money::parse($request->input('charges_amount')),
        ]);
        $data = $request->validate($this->rules() + [
            'label' => ['required', 'string', 'max:60'],
            'status' => ['required', Rule::in(array_keys(Unit::STATUSES))],
        ]);

        if (in_array($data['status'], ['occupied', 'reserved'], true) || $unit->status === 'occupied') {
            unset($data['status']);
        }

        $unit->update($data);
        $unit->refreshStatus();
        AuditLog::record('unit.updated', $unit);

        return redirect()->route('properties.show', $unit->property_id)->with('success', "Lot {$unit->label} mis à jour.");
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(config('immora.unit_types')))],
            'usage_type' => ['required', Rule::in(['habitation', 'commercial', 'mixte'])],
            'floor' => ['nullable', 'string', 'max:20'],
            'surface_m2' => ['nullable', 'numeric', 'between:1,100000'],
            'rooms' => ['nullable', 'integer', 'between:0,50'],
            'furnished' => ['nullable', 'boolean'],
            'base_rent_amount' => ['required', 'integer', 'min:1000', 'max:1000000000'],
            'charges_amount' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'deposit_months' => ['required', 'integer', 'between:0,12'],
            'advance_months' => ['required', 'integer', 'between:0,12'],
        ];
    }
}
