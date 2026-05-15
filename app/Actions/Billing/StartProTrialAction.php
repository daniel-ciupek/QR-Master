<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\PlanTier;
use App\Models\User;

final class StartProTrialAction
{
    public function handle(User $user): void
    {
        $user->update([
            'plan_tier' => PlanTier::Pro->value,
            'trial_ends_at' => now()->addDays(14),
        ]);
    }
}
