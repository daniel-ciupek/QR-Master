<?php

declare(strict_types=1);

namespace App\Actions\AbTest;

use App\Models\AbVariant;

final class UpdateVariantAction
{
    public function handle(AbVariant $variant, string $name, string $url, int $weight): void
    {
        $variant->update([
            'name' => $name,
            'destination_url' => $url,
            'weight' => max(1, min(100, $weight)),
        ]);
    }
}
