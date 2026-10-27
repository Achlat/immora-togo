<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\CurrentAgency;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Back-office réservé aux collaborateurs de l'agence. */
final class EnsureStaff
{
    public function __construct(private readonly CurrentAgency $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($this->current->role(), config('immora.staff_roles'), true)) {
            return redirect()->route('portal.home');
        }

        return $next($request);
    }
}
