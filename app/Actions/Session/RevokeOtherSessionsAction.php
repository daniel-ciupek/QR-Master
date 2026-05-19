<?php

declare(strict_types=1);

namespace App\Actions\Session;

use App\Models\UserSession;

final class RevokeOtherSessionsAction
{
    public function handle(int $userId, string $currentSessionId): void
    {
        UserSession::where('user_id', $userId)
            ->where('session_id', '!=', $currentSessionId)
            ->each(function (UserSession $session): void {
                session()->getHandler()->destroy($session->session_id);
                $session->delete();
            });
    }
}
