<?php

declare(strict_types=1);

namespace App\Notifications\Team;

use App\Models\Team;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TeamInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Team $team,
        private readonly User $invitedBy,
        private readonly string $token,
    ) {}

    /** @return array<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $acceptUrl = route('invitations.show', ['token' => $this->token]);

        return (new MailMessage)
            ->subject(__('mail.invitation.subject', ['team' => $this->team->name]).' — QR-Master')
            ->greeting(__('mail.invitation.greeting'))
            ->line(__('mail.invitation.intro', [
                'inviter' => $this->invitedBy->name,
                'team' => $this->team->name,
                'role' => __('workspace.role_editor'),
            ]))
            ->action(__('mail.invitation.action'), $acceptUrl)
            ->line(__('mail.invitation.expiry'))
            ->line(__('mail.invitation.outro'))
            ->salutation(' ');
    }
}
