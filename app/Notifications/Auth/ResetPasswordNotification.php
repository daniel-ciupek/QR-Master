<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;

final class ResetPasswordNotification extends ResetPassword
{
    public function toMail(mixed $notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);
        $expireMinutes = Config::get('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject(__('mail.reset_password.subject').' — QR-Master')
            ->greeting(__('mail.reset_password.greeting'))
            ->line(__('mail.reset_password.intro'))
            ->action(__('mail.reset_password.action'), $url)
            ->line(__('mail.reset_password.expiry', ['count' => $expireMinutes]))
            ->line(__('mail.reset_password.outro'))
            ->salutation(' ');
    }
}
