<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Owner;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Recherche globale Ctrl + K (DASH-06) : locataire, téléphone, lot, quittance, propriétaire.
 */
final class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
        $digits = preg_replace('/\D/', '', $q);
        $results = collect();

        Tenant::query()->where(function ($query) use ($like, $digits): void {
            $query->where('last_name', 'like', $like)->orWhere('first_name', 'like', $like)
                ->orWhere('company_name', 'like', $like)->orWhere('reference', 'like', $like);
            if (strlen($digits) >= 4) {
                $query->orWhere('phone_e164', 'like', "%{$digits}%");
            }
        })->limit(5)->get()->each(fn (Tenant $t) => $results->push([
            'type' => 'Locataire', 'label' => $t->displayName(), 'meta' => Phone::display($t->phone_e164), 'url' => route('tenants.show', $t),
        ]));

        Unit::query()->with('property')->where(fn ($query) => $query->where('reference', 'like', $like)->orWhere('label', 'like', $like))
            ->limit(5)->get()->each(fn (Unit $u) => $results->push([
                'type' => 'Lot', 'label' => $u->reference.' · '.$u->label, 'meta' => $u->property->name, 'url' => route('properties.show', $u->property_id),
            ]));

        Owner::query()->where(fn ($query) => $query->where('last_name', 'like', $like)->orWhere('company_name', 'like', $like)->orWhere('reference', 'like', $like))
            ->limit(4)->get()->each(fn (Owner $o) => $results->push([
                'type' => 'Propriétaire', 'label' => $o->displayName(), 'meta' => $o->reference, 'url' => route('owners.show', $o),
            ]));

        Invoice::query()->with('tenant')->where(fn ($query) => $query->where('receipt_number', 'like', $like)->orWhere('number', 'like', $like))
            ->limit(4)->get()->each(fn (Invoice $i) => $results->push([
                'type' => $i->receipt_number ? 'Quittance' : 'Échéance', 'label' => $i->receipt_number ?? $i->number,
                'meta' => $i->tenant->displayName(), 'url' => route('invoices.show', $i),
            ]));

        Payment::query()->with('tenant')->where('number', 'like', $like)->limit(4)->get()->each(fn (Payment $p) => $results->push([
            'type' => 'Reçu', 'label' => $p->number, 'meta' => $p->tenant->displayName(), 'url' => route('payments.show', $p),
        ]));

        return response()->json(['success' => true, 'data' => $results->take(12)->values()]);
    }
}
