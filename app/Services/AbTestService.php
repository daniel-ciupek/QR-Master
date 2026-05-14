<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AbTest;
use App\Models\AbVariant;

final class AbTestService
{
    public function selectVariant(AbTest $test): ?AbVariant
    {
        $variants = $test->variants()->get();

        if ($variants->isEmpty()) {
            return null;
        }

        // Always use winner if one is selected
        $winner = $variants->firstWhere('is_winner', true);
        if ($winner instanceof AbVariant) {
            return $winner;
        }

        $totalWeight = $variants->sum('weight');
        if ($totalWeight <= 0) {
            return $variants->first();
        }

        $random = random_int(1, (int) $totalWeight);
        $cumulative = 0;

        foreach ($variants as $variant) {
            $cumulative += $variant->weight;
            if ($random <= $cumulative) {
                return $variant;
            }
        }

        return $variants->last();
    }

    public function recordVariantScan(AbVariant $variant, AbTest $test): void
    {
        $variant->increment('scan_count');

        if ($test->auto_select_winner && ! $test->hasWinner()) {
            $totalScans = (int) $test->variants()->sum('scan_count');
            if ($totalScans >= $test->auto_select_threshold) {
                $this->autoSelectWinner($test);
            }
        }
    }

    public function selectWinner(AbTest $test, AbVariant $winner): void
    {
        $test->variants()->update(['is_winner' => false]);
        $winner->update(['is_winner' => true]);
    }

    private function autoSelectWinner(AbTest $test): void
    {
        $winner = $test->variants()->orderByDesc('scan_count')->first();
        if ($winner instanceof AbVariant) {
            $test->variants()->update(['is_winner' => false]);
            $winner->update(['is_winner' => true]);
        }
    }
}
