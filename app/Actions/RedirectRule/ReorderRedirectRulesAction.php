<?php

declare(strict_types=1);

namespace App\Actions\RedirectRule;

use App\Models\QrCode;
use Illuminate\Support\Facades\DB;

final class ReorderRedirectRulesAction
{
    /** @param int[] $orderedIds */
    public function handle(QrCode $qrCode, array $orderedIds): void
    {
        DB::transaction(function () use ($qrCode, $orderedIds): void {
            foreach ($orderedIds as $priority => $ruleId) {
                $qrCode->redirectRules()
                    ->where('id', $ruleId)
                    ->update(['priority' => $priority]);
            }
        });
    }
}
