<?php

declare(strict_types=1);

namespace App\Actions\AbTest;

use App\Models\AbTest;
use App\Models\AbVariant;

final class UpsertVariantAction
{
    public function create(AbTest $test, string $name, string $url, int $weight): AbVariant
    {
        return $test->variants()->create([
            'name' => $name,
            'destination_url' => $url,
            'weight' => max(1, min(100, $weight)),
            'scan_count' => 0,
            'is_winner' => false,
        ]);
    }

    public function update(AbVariant $variant, string $name, string $url, int $weight): void
    {
        $variant->update([
            'name' => $name,
            'destination_url' => $url,
            'weight' => max(1, min(100, $weight)),
        ]);
    }
}
