<?php

declare(strict_types=1);

namespace App\Actions\BioLink;

use App\Models\BioLink;
use Illuminate\Support\Facades\DB;

final class ReorderBioLinkItemsAction
{
    /** @param int[] $orderedIds */
    public function handle(BioLink $bioLink, array $orderedIds): void
    {
        DB::transaction(function () use ($bioLink, $orderedIds): void {
            foreach ($orderedIds as $position => $itemId) {
                $bioLink->items()
                    ->where('id', $itemId)
                    ->update(['sort_order' => $position]);
            }
        });
    }
}
