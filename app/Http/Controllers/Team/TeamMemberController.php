<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Actions\Team\RemoveTeamMemberAction;
use App\Actions\Team\UpdateTeamMemberRoleAction;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class TeamMemberController
{
    public function updateRole(
        Request $request,
        Team $team,
        User $user,
        UpdateTeamMemberRoleAction $action,
    ): RedirectResponse {
        Gate::authorize('manageMembers', $team);

        $validated = $request->validate([
            'role' => ['required', new Enum(TeamRole::class)],
        ]);

        /** @var User $actor */
        $actor = auth()->user();

        try {
            $action->handle($team, $user, TeamRole::from($validated['role']), $actor);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', __('workspace.role_updated'));
    }

    public function destroy(
        Team $team,
        User $user,
        RemoveTeamMemberAction $action,
    ): RedirectResponse {
        Gate::authorize('manageMembers', $team);

        /** @var User $actor */
        $actor = auth()->user();

        try {
            $action->handle($team, $user, $actor);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', __('workspace.member_removed'));
    }

    public function leave(Team $team, RemoveTeamMemberAction $action): RedirectResponse
    {
        /** @var User $actor */
        $actor = auth()->user();

        try {
            $action->handle($team, $actor, $actor);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('workspaces.index')
            ->with('success', __('workspace.left'));
    }
}
