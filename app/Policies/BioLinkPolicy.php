<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BioLink;
use App\Models\User;

final class BioLinkPolicy
{
    public function view(User $user, BioLink $bioLink): bool
    {
        return $user->id === $bioLink->user_id;
    }

    public function update(User $user, BioLink $bioLink): bool
    {
        return $user->id === $bioLink->user_id;
    }

    public function delete(User $user, BioLink $bioLink): bool
    {
        return $user->id === $bioLink->user_id;
    }
}
