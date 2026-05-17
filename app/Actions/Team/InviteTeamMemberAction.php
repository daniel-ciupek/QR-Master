<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\Team\TeamInvitationNotification;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class InviteTeamMemberAction
{
    /** @throws ValidationException */
    public function handle(Team $team, string $email, TeamRole $role, User $invitedBy): TeamInvitation
    {
        // Cannot invite an existing member
        if ($team->members()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['This person is already a member of the workspace.'],
            ]);
        }

        // Cannot assign Owner role via invitation
        if ($role === TeamRole::Owner) {
            throw ValidationException::withMessages([
                'role' => ['You cannot invite someone as Owner. Transfer ownership after they join.'],
            ]);
        }

        // Upsert: resend if already invited (overwrite expired/pending)
        $invitation = TeamInvitation::updateOrCreate(
            ['team_id' => $team->id, 'email' => $email],
            [
                'invited_by' => $invitedBy->id,
                'role' => $role->value,
                'token' => Str::random(64),
                'expires_at' => now()->addDays(7),
                'accepted_at' => null,
            ],
        );

        Notification::route('mail', $invitation->email)
            ->notify(new TeamInvitationNotification($team, $invitedBy, $invitation->token));

        AuditLogger::log($team, 'member.invited', "Invited {$email} as {$role->value}.", $invitedBy, null, ['email' => $email, 'role' => $role->value]);

        return $invitation;
    }
}
