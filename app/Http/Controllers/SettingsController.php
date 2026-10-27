<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Support\CurrentAgency;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Paramétrage de l'agence : tout ce qui varie d'un client à l'autre (CDC chap. 20). */
final class SettingsController extends Controller
{
    public function edit(CurrentAgency $current): View
    {
        return view('settings.edit', ['agency' => $current->get()]);
    }

    public function update(Request $request, CurrentAgency $current): RedirectResponse
    {
        $agency = $current->get();
        $request->merge(['phone' => Phone::normalize($request->input('phone_raw'), $agency->country_code)]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'rccm' => ['nullable', 'string', 'max:60'],
            'tax_id' => ['nullable', 'string', 'max:60'],
            'license_number' => ['nullable', 'string', 'max:60'],
            'vat_rate' => ['required', 'numeric', 'between:0,30'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'settings.due_day' => ['required', 'integer', 'between:1,28'],
            'settings.grace_days' => ['required', 'integer', 'between:0,30'],
            'settings.penalty_rate' => ['required', 'numeric', 'between:0,50'],
            'settings.default_deposit_months' => ['required', 'integer', 'between:0,12'],
            'settings.default_advance_months' => ['required', 'integer', 'between:0,12'],
            'settings.payout_dual_control_amount' => ['required', 'integer', 'min:0'],
            'settings.online_fee_payer' => ['required', Rule::in(['agency', 'tenant'])],
            'settings.rent_before_penalties' => ['nullable', 'boolean'],
            'settings.enforce_two_factor' => ['nullable', 'boolean'],
        ]);

        $settings = array_merge($agency->settings ?? [], $data['settings'], [
            'rent_before_penalties' => (bool) ($data['settings']['rent_before_penalties'] ?? false),
            'enforce_two_factor' => (bool) ($data['settings']['enforce_two_factor'] ?? false),
        ]);

        $attributes = collect($data)->except(['logo', 'settings'])->all() + ['settings' => $settings];
        if ($request->hasFile('logo')) {
            $attributes['logo_path'] = $request->file('logo')->store("agencies/{$agency->id}", 'public');
        }

        $agency->update($attributes);
        AuditLog::record('settings.updated', $agency);

        return back()->with('success', 'Paramètres enregistrés.');
    }
}
