<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

final class RemoveTeamMemberAction
{
    /** @throws ValidationException */
    public function handle(Team $team, User $target, User $actor): void
    {
        if (! $team->hasMember($target)) {
            throw ValidationException::withMessages([
                'user' => ['This user is not a member of the workspace.'],
            ]);
        }

        // Owner cannot be removed — transfer ownership first
        if ($team->userRole($target) === TeamRole::Owner) {
            throw ValidationException::withMessages([
                'user' => ['The workspace owner cannot be removed. Transfer ownership first.'],
            ]);
        }

        // Members can leave on their own; admins/owners can remove others
        if ($actor->id !== $target->id) {
            $actorRole = $team->userRole($actor);
            if ($actorRole === null || ! $actorRole->canManageMembers()) {
                throw ValidationException::withMessages([
                    'user' => ['You do not have permission to remove members.'],
                ]);
            }
        }

        // Reset current_team_id if the user was in this team context
        if ($target->current_team_id === $team->id) {
            $target->update(['current_team_id' => null]);
        }

        $team->members()->detach($target->id);

        $desc = $actor->id === $target->id
            ? "{$target->name} left the workspace."
            : "{$actor->name} removed {$target->name} from the workspace.";

        AuditLogger::log($team, 'member.removed', $desc, $actor, $target);
    }
}
