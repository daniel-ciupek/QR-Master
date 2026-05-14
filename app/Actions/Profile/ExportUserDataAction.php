<?php

declare(strict_types=1);

namespace App\Actions\Profile;

use App\Models\QrCode;
use App\Models\ScanLog;
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
                ->get(['ip_hash', 'user_agent', 'last_active_at', 'created_at'])
                ->map(fn (UserSession $s) => [
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
            'qr_codes' => QrCode::where('user_id', $user->id)
                ->withTrashed()
                ->orderByDesc('created_at')
                ->get(['id', 'title', 'type', 'short_hash', 'destination_url', 'is_active', 'created_at', 'deleted_at'])
                ->map(fn (QrCode $qr) => [
                    'title' => $qr->title,
                    'type' => $qr->type->value,
                    'short_hash' => $qr->short_hash,
                    'destination_url' => $qr->destination_url,
                    'is_active' => $qr->is_active,
                    'created_at' => $qr->created_at->toIso8601String(),
                    'deleted_at' => $qr->deleted_at?->toIso8601String(),
                    'scan_count' => ScanLog::where('qr_code_id', $qr->id)->count(),
                    'scans' => ScanLog::where('qr_code_id', $qr->id)
                        ->orderByDesc('scanned_at')
                        ->limit(500)
                        ->get(['scanned_at', 'country', 'city', 'device_type', 'os', 'browser', 'language'])
                        ->map(fn (ScanLog $log) => [
                            'scanned_at' => $log->scanned_at->toIso8601String(),
                            'country' => $log->country,
                            'city' => $log->city,
                            'device_type' => $log->device_type,
                            'os' => $log->os,
                            'browser' => $log->browser,
                            'language' => $log->language,
                        ])
                        ->all(),
                ])
                ->all(),
        ];
    }
}
