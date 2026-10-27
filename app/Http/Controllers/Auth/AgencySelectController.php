<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\SessionStarter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Choix de l'espace de travail quand un utilisateur appartient à plusieurs agences (AUTH-11).
 */
final class AgencySelectController extends Controller
{
    public function __construct(private readonly SessionStarter $starter) {}

    public function show(Request $request): View|RedirectResponse
    {
        $agencies = $request->user()->activeAgencies()->orderBy('name')->get();
        if ($agencies->isEmpty() && $request->user()->is_super_admin) {
            return redirect()->route('admin.agencies.index');
        }

        return view('auth.agency-select', ['agencies' => $agencies]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['agency_id' => ['required', 'integer']]);
        $user = $request->user();

        abort_unless($user->activeAgencies()->where('agencies.id', $data['agency_id'])->exists(), 403);

        $user->forceFill(['last_agency_id' => $data['agency_id']])->save();
        $request->session()->forget('agency_id');

        return $this->starter->redirectAfterLogin($user, $request);
    }
}
