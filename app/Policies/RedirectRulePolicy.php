<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RedirectRule;
use App\Models\User;

final class RedirectRulePolicy
{
    public function update(User $user, RedirectRule $rule): bool
    {
        return $user->id === $rule->qrCode?->user_id;
    }

    public function delete(User $user, RedirectRule $rule): bool
    {
        return $user->id === $rule->qrCode?->user_id;
    }
}
