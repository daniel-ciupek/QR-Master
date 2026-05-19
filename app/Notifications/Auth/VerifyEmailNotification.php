<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use Carbon\Carbon;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

final class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public function toMail(mixed $notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);
        $expireMinutes = Config::get('auth.verification.expire', 60);

        return (new MailMessage)
            ->subject(__('mail.verify_email.subject').' — QR-Master')
            ->greeting(__('mail.verify_email.greeting'))
            ->line(__('mail.verify_email.intro'))
            ->action(__('mail.verify_email.action'), $url)
            ->line(__('mail.verify_email.expiry', ['count' => $expireMinutes]))
            ->line(__('mail.verify_email.outro'))
            ->salutation(' ');
    }

    protected function verificationUrl(mixed $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );
    }
}
