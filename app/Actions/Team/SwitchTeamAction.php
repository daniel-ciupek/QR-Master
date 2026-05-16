<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class SwitchTeamAction
{
    /** @throws AuthorizationException */
    public function handle(User $user, ?Team $team): void
    {
        if ($team !== null && ! $user->belongsToTeam($team)) {
            throw new AuthorizationException('You are not a member of this workspace.');
        }

        $user->update(['current_team_id' => $team?->id]);
    }
}
