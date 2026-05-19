<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\Billing\SubscriptionExpiringSoonNotification;
use Illuminate\Console\Command;

final class SendSubscriptionExpiryReminders extends Command
{
    protected $signature = 'billing:send-expiry-reminders';

    protected $description = 'Send 7-day expiry reminders for trials and cancelled subscriptions';

    public function handle(): int
    {
        $targetDate = now()->addDays(7)->toDateString();

        // Trial expiry reminders
        User::whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', $targetDate)
            ->each(function (User $user): void {
                $user->notify(new SubscriptionExpiringSoonNotification(
                    planName: $user->plan_tier->label(),
                    expiresAt: $user->trial_ends_at?->toDateString() ?? '',
                    isTrial: true,
                ));
            });

        // Cancelled subscription reminders
        User::whereHas('subscriptions', function ($query): void {
            $query->whereNotNull('ends_at')
                ->whereDate('ends_at', now()->addDays(7)->toDateString());
        })->each(function (User $user): void {
            $subscription = $user->subscription('default');

            if ($subscription === null || $subscription->ends_at === null) {
                return;
            }

            $user->notify(new SubscriptionExpiringSoonNotification(
                planName: $user->plan_tier->label(),
                expiresAt: $subscription->ends_at->toDateString(),
                isTrial: false,
            ));
        });

        $this->info('Expiry reminders sent.');

        return self::SUCCESS;
    }
}
