<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuthEvent;
use App\Services\Auth\DeviceService;
use App\Support\CurrentAgency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Sessions et journal d'authentification (AUTH-12, AUTH-20). */
final class SecurityController extends Controller
{
    public function index(Request $request, DeviceService $devices, CurrentAgency $current): View
    {
        $user = $request->user();
        $sessions = DB::table('sessions')->where('user_id', $user->id)->orderByDesc('last_activity')->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'device' => $devices->describe(Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => (string) $s->user_agent])),
                'ip' => $s->ip_address,
                'last' => now()->setTimestamp((int) $s->last_activity),
                'current' => $s->id === $request->session()->getId(),
            ]);

        $isAdmin = $current->role() === 'admin';
        $events = AuthEvent::query()->with('user')
            ->when($isAdmin, fn ($q) => $q->whereIn('user_id', $current->get()->users()->pluck('users.id')))
            ->when(! $isAdmin, fn ($q) => $q->where('user_id', $user->id))
            ->latest('created_at')->limit(50)->get();

        return view('security.index', ['sessions' => $sessions, 'events' => $events, 'isAdmin' => $isAdmin]);
    }

    public function revoke(Request $request, string $session): RedirectResponse
    {
        DB::table('sessions')->where('id', $session)->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())->delete();

        return back()->with('success', 'Appareil déconnecté.');
    }

    public function revokeOthers(Request $request): RedirectResponse
    {
        $user = $request->user();
        DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();
        $user->forceFill(['remember_token' => Str::random(60)])->save();
        AuthEvent::log('sessions_revoked', $user->id);

        return back()->with('success', 'Tous vos autres appareils ont été déconnectés.');
    }
}
