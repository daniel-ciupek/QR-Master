<?php

declare(strict_types=1);

namespace App\Actions\Profile;

use App\Models\QrCode;
use App\Models\ScanLog;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        DB::transaction(function () use ($user): void {
            // Delete scan logs before QR codes (foreign key order)
            $qrCodeIds = QrCode::withTrashed()
                ->where('user_id', $user->id)
                ->pluck('id');

            ScanLog::whereIn('qr_code_id', $qrCodeIds)->delete();

            QrCode::withTrashed()->where('user_id', $user->id)->forceDelete();

            UserSession::where('user_id', $user->id)->delete();
            $user->webAuthnCredentials()->delete();

            if ($user->hasEnabledTwoFactorAuthentication()) {
                $user->forceFill([
                    'two_factor_secret' => null,
                    'two_factor_recovery_codes' => null,
                    'two_factor_confirmed_at' => null,
                ])->save();
            }

            $user->delete();
        });

        Auth::logout();
    }
}
