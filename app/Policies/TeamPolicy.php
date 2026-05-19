<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

final class TeamPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function update(User $user, Team $team): bool
    {
        return $this->hasRole($user, $team, TeamRole::Admin);
    }

    public function delete(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id;
    }

    public function manageMembers(User $user, Team $team): bool
    {
        return $this->hasRole($user, $team, TeamRole::Admin);
    }

    public function manageBilling(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id;
    }

    private function hasRole(User $user, Team $team, TeamRole $minimum): bool
    {
        $role = $user->teamRole($team);

        if ($role === null) {
            return false;
        }

        $hierarchy = [TeamRole::Owner, TeamRole::Admin, TeamRole::Editor, TeamRole::Viewer];

        return array_search($role, $hierarchy, true) <= array_search($minimum, $hierarchy, true);
    }
}
