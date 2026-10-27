<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes de sécurité (CDC chap. 25). Alpine.js évalue ses expressions, d'où 'unsafe-eval'.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $vite = app()->isLocal() ? ' http://localhost:5173 ws://localhost:5173 http://[::1]:5173 ws://[::1]:5173' : '';
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-eval' https://challenges.cloudflare.com{$vite}",
            "style-src 'self' 'unsafe-inline'{$vite}",
            "img-src 'self' data: blob:",
            "font-src 'self' data:{$vite}",
            "connect-src 'self'{$vite}",
            'frame-src https://challenges.cloudflare.com',
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self' https:",
            "frame-ancestors 'none'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
