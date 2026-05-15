<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SubscriptionExpiringSoonNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $planName,
        public readonly string $expiresAt,
        public readonly bool $isTrial,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('billing.email.expiringSoon.subject'))
            ->markdown('emails.billing.expiring-soon', [
                'planName' => $this->planName,
                'expiresAt' => $this->expiresAt,
                'isTrial' => $this->isTrial,
                'upgradeUrl' => route('pricing'),
                'portalUrl' => route('billing.portal'),
            ]);
    }
}
