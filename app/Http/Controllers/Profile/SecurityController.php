<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SecurityController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $twoFactorQrCode = null;
        $twoFactorSetupKey = null;
        $twoFactorRecoveryCodes = null;

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $twoFactorRecoveryCodes = $user->recoveryCodes();
        } elseif ($user->two_factor_secret !== null) {
            // 2FA włączone ale jeszcze niepotwierdzone — pokaż QR
            $twoFactorQrCode = $user->twoFactorQrCodeSvg();
            $twoFactorSetupKey = decrypt($user->two_factor_secret);
        }

        return Inertia::render('Profile/Security', [
            'twoFactorQrCode' => $twoFactorQrCode,
            'twoFactorSetupKey' => $twoFactorSetupKey,
            'twoFactorRecoveryCodes' => $twoFactorRecoveryCodes,
        ]);
    }
}
