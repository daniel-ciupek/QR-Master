<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Data\Team\CreateTeamData;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

final class CreateTeamAction
{
    public function handle(User $owner, CreateTeamData $data): Team
    {
        $team = Team::create([
            'owner_id' => $owner->id,
            'name' => $data->name,
            'settings' => [],
        ]);

        // Owner automatically gets Owner role as the first member
        $team->members()->attach($owner->id, [
            'role' => TeamRole::Owner->value,
            'joined_at' => now(),
        ]);

        // Switch user to the new team context
        $owner->update(['current_team_id' => $team->id]);

        return $team;
    }
}
