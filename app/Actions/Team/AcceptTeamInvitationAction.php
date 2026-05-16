<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class AcceptTeamInvitationAction
{
    /** @throws ValidationException */
    public function handle(TeamInvitation $invitation, User $user): void
    {
        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'invitation' => ['This invitation has expired or was already used.'],
            ]);
        }

        if (strtolower($user->email) !== strtolower($invitation->email)) {
            throw ValidationException::withMessages([
                'invitation' => ['This invitation was sent to a different email address.'],
            ]);
        }

        $team = $invitation->team()->firstOrFail();

        if ($team->hasMember($user)) {
            throw ValidationException::withMessages([
                'invitation' => ['You are already a member of this workspace.'],
            ]);
        }

        $team->members()->attach($user->id, [
            'role' => $invitation->role->value,
            'joined_at' => now(),
        ]);

        $invitation->update(['accepted_at' => now()]);

        // Auto-switch to the new team
        $user->update(['current_team_id' => $invitation->team_id]);
    }
}
