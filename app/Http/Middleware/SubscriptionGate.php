<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\CurrentAgency;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abonnement expiré : l'agence passe en lecture seule, ses données restent consultables (SAAS-03).
 */
final class SubscriptionGate
{
    public function __construct(private readonly CurrentAgency $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $subscription = $this->current->get()->subscription()->with('plan')->first();
        View::share('subscription', $subscription);

        $active = $subscription?->isActive() ?? false;
        if (! $active && ! $request->isMethodSafe() && ! $request->routeIs('logout')) {
            $message = $this->current->role() === 'admin'
                ? 'Votre abonnement a expiré. Renouvelez-le pour retrouver l\'accès complet ; vos données sont conservées.'
                : 'L\'accès est momentanément en lecture seule. Votre administrateur a été prévenu.';

            return back()->with('error', $message);
        }

        return $next($request);
    }
}
