<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public static function log(
        Team $team,
        string $action,
        string $description,
        ?User $actor = null,
        ?Model $subject = null,
        ?array $metadata = null,
    ): void {
        $ipHash = null;

        try {
            /** @var Request $request */
            $request = app(Request::class);
            $ip = $request->ip();

            if ($ip !== null && $ip !== '127.0.0.1' && $ip !== '::1') {
                $salt = (string) config('app.key');
                $ipHash = hash_hmac('sha256', $ip, $salt);
            }
        } catch (\Throwable) {
            // CLI context or test — no IP available
        }

        AuditLog::create([
            'team_id' => $team->id,
            'user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'metadata' => $metadata,
            'ip_hash' => $ipHash,
        ]);
    }
}
