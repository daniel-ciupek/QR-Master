<?php

declare(strict_types=1);

namespace App\Actions\BioLink;

use App\Models\BioLinkItem;

final class DeleteBioLinkItemAction
{
    public function handle(BioLinkItem $item): void
    {
        $item->delete();
    }
}
