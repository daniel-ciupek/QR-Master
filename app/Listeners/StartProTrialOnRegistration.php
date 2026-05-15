<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Billing\GenerateReferralCodeAction;
use App\Actions\Billing\StartProTrialAction;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Session;

final class StartProTrialOnRegistration
{
    public function __construct(
        private readonly StartProTrialAction $trialAction,
        private readonly GenerateReferralCodeAction $referralAction,
    ) {}

    public function handle(Registered $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->trialAction->handle($event->user);
        $this->referralAction->handle($event->user);

        $refCode = Session::get('affiliate_ref');
        if (is_string($refCode) && $refCode !== '') {
            $referrer = User::where('referral_code', $refCode)->first();
            if ($referrer !== null && $referrer->id !== $event->user->id) {
                $event->user->update(['referred_by_user_id' => $referrer->id]);
            }
            Session::forget('affiliate_ref');
        }
    }
}
