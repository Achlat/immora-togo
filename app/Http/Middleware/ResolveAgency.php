<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Agency;
use App\Support\CurrentAgency;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Détermine l'agence active et vérifie l'appartenance de l'utilisateur (cloisonnement multi-agences).
 */
final class ResolveAgency
{
    public function __construct(private readonly CurrentAgency $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $agencyId = $request->session()->get('agency_id');
        $agency = $agencyId ? Agency::query()->find($agencyId) : null;
        $role = $agency !== null ? $user->roleIn($agency) : null;

        if ($agency === null || $role === null) {
            $request->session()->forget('agency_id');

            return redirect()->route('agency.select');
        }

        $this->current->set($agency, $role);
        View::share('currentAgency', $agency);
        View::share('currentRole', $role);

        return $next($request);
    }
}
