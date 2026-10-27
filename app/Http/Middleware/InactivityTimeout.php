<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AuthEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Déconnexion après inactivité (AUTH-14) : 30 min en back-office, 15 min pour le super-admin.
 * Un utilisateur qui a coché « Rester connecté » est reconnu par son jeton et n'est pas concerné.
 */
final class InactivityTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        $limit = $user->is_super_admin ? 15 : config('immora.inactivity_minutes');
        $last = (int) $request->session()->get('last_activity_at', now()->timestamp);
        $remembered = $request->cookies->has(Auth::guard()->getRecallerName());

        if (! $remembered && now()->timestamp - $last > $limit * 60) {
            AuthEvent::log('logout', $user->id, ['reason' => 'inactivity']);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status',
                "Vous avez été déconnecté après {$limit} minutes d'inactivité. Reconnectez-vous pour reprendre où vous en étiez.");
        }

        $request->session()->put('last_activity_at', now()->timestamp);

        return $next($request);
    }
}
