<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Actions\Team\CreateTeamAction;
use App\Data\Team\CreateTeamData;
use App\Data\Team\UpdateTeamData;
use App\Http\Requests\Team\CreateTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TeamController
{
    public function index(): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $teams = $user->teams()
            ->withPivot(['role', 'joined_at'])
            ->withCount('members')
            ->get();

        return Inertia::render('Workspace/Index', [
            'teams' => $teams->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                /** @phpstan-ignore-next-line property.notFound */
                'role' => $team->pivot->role,
                'membersCount' => $team->members_count,
                'isCurrent' => $user->current_team_id === $team->id,
                'isOwner' => $team->owner_id === $user->id,
            ]),
            'currentTeamId' => $user->current_team_id,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Workspace/Create');
    }

    public function store(CreateTeamRequest $request, CreateTeamAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $team = $action->handle($user, CreateTeamData::from($request->validated()));

        return redirect()->route('workspaces.show', $team)
            ->with('success', __('workspace.created'));
    }

    public function show(Team $team): Response
    {
        Gate::authorize('view', $team);

        /** @var User $user */
        $user = auth()->user();

        $team->load(['members', 'owner', 'pendingInvitations.invitedBy']);

        $myRole = $user->teamRole($team);

        return Inertia::render('Workspace/Show', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'owner' => $team->owner !== null
                    ? ['id' => $team->owner->id, 'name' => $team->owner->name]
                    : null,
                'members' => $team->members->map(fn (User $member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    /** @phpstan-ignore-next-line property.notFound */
                    'role' => $member->pivot->role,
                    /** @phpstan-ignore-next-line property.notFound */
                    'joinedAt' => $member->pivot->joined_at,
                ]),
                'isOwner' => $team->owner_id === $user->id,
                'myRole' => $myRole?->value,
                'myId' => $user->id,
                'pendingInvitations' => $team->pendingInvitations->map(fn (TeamInvitation $inv) => [
                    'id' => $inv->id,
                    'email' => $inv->email,
                    'role' => $inv->role->value,
                    'expiresAt' => $inv->expires_at->toIso8601String(),
                ]),
            ],
        ]);
    }

    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        Gate::authorize('update', $team);

        $data = UpdateTeamData::from($request->validated());
        $team->update(['name' => $data->name]);

        return back()->with('success', __('workspace.updated'));
    }

    public function destroy(Team $team): RedirectResponse
    {
        Gate::authorize('delete', $team);

        /** @var User $user */
        $user = auth()->user();

        if ($user->current_team_id === $team->id) {
            $user->update(['current_team_id' => null]);
        }

        $team->delete();

        return redirect()->route('workspaces.index')
            ->with('success', __('workspace.deleted'));
    }
}
