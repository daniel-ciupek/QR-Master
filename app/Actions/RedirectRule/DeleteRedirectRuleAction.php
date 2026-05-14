<?php

declare(strict_types=1);

namespace App\Actions\RedirectRule;

use App\Models\RedirectRule;

final class DeleteRedirectRuleAction
{
    public function handle(RedirectRule $rule): void
    {
        $rule->delete();
    }
}
