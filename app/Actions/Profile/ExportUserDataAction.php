<?php

declare(strict_types=1);

namespace App\Actions\Profile;

use App\Models\User;
use App\Models\UserSession;

final class ExportUserDataAction
{
    /** @return array<string, mixed> */
    public function handle(User $user): array
    {
        return [
            'exported_at' => now()->toIso8601String(),
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'two_factor' => [
                'enabled' => $user->hasEnabledTwoFactorAuthentication(),
                'confirmed_at' => $user->two_factor_confirmed_at?->toIso8601String(),
            ],
            'sessions' => UserSession::where('user_id', $user->id)
                ->orderByDesc('last_active_at')
                ->get(['ip_address', 'user_agent', 'last_active_at', 'created_at'])
                ->map(fn (UserSession $s) => [
                    'ip_address' => $s->ip_address,
                    'browser' => $s->parsedBrowser(),
                    'os' => $s->parsedOs(),
                    'last_active_at' => $s->last_active_at->toIso8601String(),
                    'created_at' => $s->created_at->toIso8601String(),
                ])
                ->all(),
            'passkeys' => $user->webAuthnCredentials()
                ->get(['name', 'created_at'])
                ->map(static function ($credential): array {
                    return [
                        'name' => (string) ($credential->getAttribute('name') ?? 'Passkey'),
                        'created_at' => $credential->getAttribute('created_at')?->toIso8601String(),
                    ];
                })
                ->all(),
            'qr_codes' => [], // populated in Etap 2
        ];
    }
}
