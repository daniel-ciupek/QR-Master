<?php

declare(strict_types=1);

namespace App\Actions\Profile;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class DeleteAccountAction
{
    /**
     * @throws ValidationException
     */
    public function handle(User $user, string $password): void
    {
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => [__('auth.password')],
            ]);
        }

        UserSession::where('user_id', $user->id)->delete();
        $user->webAuthnCredentials()->delete();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $user->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ])->save();
        }

        Auth::logout();

        $user->delete();
    }
}
