<?php

declare(strict_types=1);

namespace App\Actions\RedirectRule;

use App\Models\QrCode;
use App\Models\RedirectRule;

final class CreateRedirectRuleAction
{
    /** @param array<int, array<string, mixed>> $conditions */
    public function handle(
        QrCode $qrCode,
        ?string $name,
        array $conditions,
        string $destinationUrl,
    ): RedirectRule {
        $nextPriority = $qrCode->redirectRules()->max('priority') ?? -1;

        return $qrCode->redirectRules()->create([
            'name' => $name,
            'conditions' => $conditions,
            'destination_url' => $destinationUrl,
            'priority' => $nextPriority + 1,
            'is_active' => true,
        ]);
    }
}
