<?php

declare(strict_types=1);

namespace App\Actions\Session;

use App\Models\UserSession;

final class RevokeSessionAction
{
    public function handle(UserSession $session): void
    {
        session()->getHandler()->destroy($session->session_id);
        $session->delete();
    }
}
