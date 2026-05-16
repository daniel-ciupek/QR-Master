<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class UpdateTeamMemberRoleAction
{
    /** @throws ValidationException */
    public function handle(Team $team, User $target, TeamRole $newRole, User $actor): void
    {
        if (! $team->hasMember($target)) {
            throw ValidationException::withMessages([
                'user' => ['This user is not a member of the workspace.'],
            ]);
        }

        $targetCurrentRole = $team->userRole($target);

        // Cannot change the Owner role without being the owner yourself
        if ($targetCurrentRole === TeamRole::Owner && $actor->id !== $team->owner_id) {
            throw ValidationException::withMessages([
                'role' => ["Only the workspace owner can change the owner's role."],
            ]);
        }

        // Assigning Owner role transfers ownership
        if ($newRole === TeamRole::Owner) {
            // Demote current owner to Admin
            $team->members()->updateExistingPivot($team->owner_id, ['role' => TeamRole::Admin->value]);
            $team->update(['owner_id' => $target->id]);
        }

        $team->members()->updateExistingPivot($target->id, ['role' => $newRole->value]);
    }
}
