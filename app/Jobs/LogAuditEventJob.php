<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class LogAuditEventJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    public bool $failOnTimeout = true;

    public function __construct(
        public readonly int $teamId,
        public readonly string $action,
        public readonly string $description,
        public readonly ?int $actorId,
        public readonly ?string $subjectType,
        public readonly int|string|null $subjectId,
        public readonly ?string $ipHash,
        /** @var array<string, mixed>|null */
        public readonly ?array $metadata,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        AuditLog::create([
            'team_id' => $this->teamId,
            'user_id' => $this->actorId,
            'action' => $this->action,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'ip_hash' => $this->ipHash,
        ]);
    }
}
