<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class PlanLimitReachedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $limitType,
        public readonly int $used,
        public readonly int $limit,
    ) {
        $this->onQueue('notifications');
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'plan_limit_reached',
            'limit_type' => $this->limitType,
            'used' => $this->used,
            'limit' => $this->limit,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toBroadcast(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
