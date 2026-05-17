<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\LogAuditEventJob;
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
        dispatch(new LogAuditEventJob(
            teamId: $team->id,
            action: $action,
            description: $description,
            actorId: $actor?->id,
            subjectType: $subject ? class_basename($subject) : null,
            subjectId: $subject?->getKey(),
            ipHash: self::resolveIpHash(),
            metadata: $metadata,
        ));
    }

    private static function resolveIpHash(): ?string
    {
        try {
            /** @var Request $request */
            $request = app(Request::class);
            $ip = $request->ip();

            if ($ip === null || $ip === '127.0.0.1' || $ip === '::1') {
                return null;
            }

            $salt = (string) config('app.key');

            return hash_hmac('sha256', $ip, $salt);
        } catch (\Throwable) {
            return null;
        }
    }
}
