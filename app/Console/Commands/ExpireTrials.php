<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PlanTier;
use App\Models\User;
use Illuminate\Console\Command;

final class ExpireTrials extends Command
{
    protected $signature = 'billing:expire-trials';

    protected $description = 'Downgrade users whose Pro trial has expired and have no active subscription';

    public function handle(): int
    {
        $expired = User::where('plan_tier', PlanTier::Pro->value)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->whereDoesntHave('subscriptions', function ($query): void {
                $query->where('stripe_status', 'active');
            })
            ->get();

        foreach ($expired as $user) {
            $user->update([
                'plan_tier' => PlanTier::Free->value,
                'trial_ends_at' => null,
            ]);
        }

        $this->info("Expired {$expired->count()} trial(s) — downgraded to Free.");

        return self::SUCCESS;
    }
}
