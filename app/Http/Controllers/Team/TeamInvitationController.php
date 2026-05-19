<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Actions\Team\AcceptTeamInvitationAction;
use App\Actions\Team\InviteTeamMemberAction;
use App\Actions\Team\RevokeTeamInvitationAction;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TeamInvitationController
{
    public function store(
        Request $request,
        Team $team,
        InviteTeamMemberAction $action,
    ): RedirectResponse {
        Gate::authorize('manageMembers', $team);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', new Enum(TeamRole::class)],
        ]);

        /** @var User $user */
        $user = auth()->user();

        try {
            $action->handle($team, $validated['email'], TeamRole::from($validated['role']), $user);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', __('workspace.invitation_sent', ['email' => $validated['email']]));
    }

    public function destroy(
        Team $team,
        TeamInvitation $invitation,
        RevokeTeamInvitationAction $action,
    ): RedirectResponse {
        Gate::authorize('manageMembers', $team);

        if ($invitation->team_id !== $team->id) {
            abort(404);
        }

        $action->handle($invitation);

        return back()->with('success', __('workspace.invitation_revoked'));
    }

    public function show(string $token): Response|RedirectResponse
    {
        $invitation = TeamInvitation::with('team', 'invitedBy')
            ->where('token', $token)
            ->firstOrFail();

        if (! $invitation->isPending()) {
            return redirect()->route('dashboard')
                ->with('error', __('workspace.invitation_expired'));
        }

        $team = $invitation->team;
        assert($team instanceof Team);
        $inviter = $invitation->invitedBy;
        assert($inviter instanceof User);

        return Inertia::render('Workspace/AcceptInvitation', [
            'invitation' => [
                'token' => $invitation->token,
                'teamName' => $team->name,
                'inviterName' => $inviter->name,
                'role' => $invitation->role->label(),
                'email' => $invitation->email,
                'expiresAt' => $invitation->expires_at->toIso8601String(),
            ],
            'isLoggedIn' => auth()->check(),
            'userEmail' => auth()->user()?->email,
        ]);
    }

    public function accept(
        string $token,
        AcceptTeamInvitationAction $action,
    ): RedirectResponse {
        $invitation = TeamInvitation::where('token', $token)->firstOrFail();

        /** @var User $user */
        $user = auth()->user();

        try {
            $action->handle($invitation, $user);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $team = $invitation->team;
        assert($team instanceof Team);

        return redirect()->route('workspaces.show', $team)
            ->with('success', __('workspace.invitation_accepted', ['team' => $team->name]));
    }
}
