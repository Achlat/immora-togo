<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Owner;
use App\Models\Property;
use App\Services\SequenceService;
use App\Support\CurrentAgency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Patrimoine : biens (PROP-01, PROP-04). */
final class PropertyController extends Controller
{
    public function index(Request $request): View
    {
        $properties = Property::query()
            ->with('owner')
            ->withCount(['units', 'units as occupied_count' => fn ($q) => $q->where('status', 'occupied')])
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")
                ->orWhere('reference', 'like', "%{$term}%")->orWhere('district', 'like', "%{$term}%")))
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('properties.index', ['properties' => $properties]);
    }

    public function create(): View
    {
        return view('properties.form', ['property' => new Property(['city' => 'Lomé']), 'owners' => $this->owners()]);
    }

    public function store(Request $request, SequenceService $sequences, CurrentAgency $current): RedirectResponse
    {
        $data = $this->validated($request);
        $data['reference'] = $sequences->reference($current->id(), 'property');
        $property = Property::create($data);
        AuditLog::record('property.created', $property);

        return redirect()->route('properties.show', $property)->with('success', 'Bien créé. Ajoutez maintenant ses lots.');
    }

    public function show(Property $property): View
    {
        $property->load(['owner', 'units' => fn ($q) => $q->orderBy('label'), 'units.activeLease.tenant']);

        return view('properties.show', ['property' => $property]);
    }

    public function edit(Property $property): View
    {
        return view('properties.form', ['property' => $property, 'owners' => $this->owners()]);
    }

    public function update(Request $request, Property $property): RedirectResponse
    {
        $property->update($this->validated($request));
        AuditLog::record('property.updated', $property);

        return redirect()->route('properties.show', $property)->with('success', 'Bien mis à jour.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(array_keys(config('immora.property_types')))],
            'owner_id' => ['nullable', Rule::exists('owners', 'id')->where('agency_id', app(CurrentAgency::class)->id())],
            'city' => ['required', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:120'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'land_title' => ['nullable', 'string', 'max:80'],
            'year_built' => ['nullable', 'integer', 'between:1900,2100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    /** @return array<int, string> */
    private function owners(): array
    {
        return Owner::query()->orderBy('last_name')->get()->mapWithKeys(fn (Owner $o) => [$o->id => $o->displayName()])->all();
    }
}
