<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\CurrentAgency;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Portails propriétaire et locataire (CDC chap. 18). */
final class EnsurePortalUser
{
    public function __construct(private readonly CurrentAgency $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($this->current->role(), ['owner', 'tenant'], true)) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
