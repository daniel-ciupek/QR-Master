<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Actions\Session\RevokeOtherSessionsAction;
use App\Actions\Session\RevokeSessionAction;
use App\Http\Controllers\Controller;
use App\Models\UserSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionsController extends Controller
{
    public function index(Request $request): Response
    {
        $currentSessionId = $request->session()->getId();

        $sessions = UserSession::where('user_id', auth()->id())
            ->orderByDesc('last_active_at')
            ->get()
            ->map(fn (UserSession $s) => [
                'id' => $s->id,
                'is_current' => $s->session_id === $currentSessionId,
                'browser' => $s->parsedBrowser(),
                'os' => $s->parsedOs(),
                'ip_address' => $s->ip_address,
                'last_active_at' => $s->last_active_at->diffForHumans(),
            ]);

        return Inertia::render('Profile/Sessions', compact('sessions'));
    }

    public function destroy(Request $request, UserSession $userSession, RevokeSessionAction $action): RedirectResponse
    {
        abort_unless($userSession->user_id === auth()->id(), 403);

        $action->handle($userSession);

        return back()->with('success', 'Sesja została zakończona.');
    }

    public function destroyOthers(Request $request, RevokeOtherSessionsAction $action): RedirectResponse
    {
        $action->handle((int) auth()->id(), $request->session()->getId());

        return back()->with('success', 'Wszystkie inne sesje zostały zakończone.');
    }
}
