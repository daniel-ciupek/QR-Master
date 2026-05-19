<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Models\User;
use Illuminate\Support\Str;

final class GenerateReferralCodeAction
{
    public function handle(User $user): void
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        $user->update(['referral_code' => $code]);
    }
}
