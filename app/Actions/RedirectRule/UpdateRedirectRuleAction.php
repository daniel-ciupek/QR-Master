<?php

declare(strict_types=1);

namespace App\Actions\RedirectRule;

use App\Models\RedirectRule;

final class UpdateRedirectRuleAction
{
    /** @param array<int, array<string, mixed>> $conditions */
    public function handle(
        RedirectRule $rule,
        ?string $name,
        array $conditions,
        string $destinationUrl,
        bool $isActive,
    ): void {
        $rule->update([
            'name' => $name,
            'conditions' => $conditions,
            'destination_url' => $destinationUrl,
            'is_active' => $isActive,
        ]);
    }
}
