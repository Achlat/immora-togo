<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\CurrentAgency;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** 2FA obligatoire pour les administrateurs et comptables (AUTH-03). */
final class RequireTwoFactorSetup
{
    public function __construct(private readonly CurrentAgency $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $required = $this->current->get()->setting('enforce_two_factor', true)
            && in_array($this->current->role(), config('immora.two_factor_required_roles'), true);

        if ($required && ! $user->hasTwoFactor()) {
            return redirect()->route('two-factor.setup')
                ->with('status', 'Votre rôle exige la double authentification. Activez-la pour accéder à votre espace.');
        }

        return $next($request);
    }
}
