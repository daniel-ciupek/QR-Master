<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QrUserTemplate;
use App\Models\User;

final class QrUserTemplatePolicy
{
    public function delete(User $user, QrUserTemplate $template): bool
    {
        return $user->id === $template->user_id;
    }
}
