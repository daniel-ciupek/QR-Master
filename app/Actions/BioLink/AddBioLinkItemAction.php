<?php

declare(strict_types=1);

namespace App\Actions\BioLink;

use App\Models\BioLink;
use App\Models\BioLinkItem;

final class AddBioLinkItemAction
{
    public function handle(BioLink $bioLink, string $title, string $url, ?string $icon): BioLinkItem
    {
        $nextOrder = $bioLink->items()->max('sort_order') ?? -1;

        return $bioLink->items()->create([
            'title' => $title,
            'url' => $url,
            'icon' => $icon,
            'sort_order' => $nextOrder + 1,
            'is_active' => true,
            'click_count' => 0,
        ]);
    }
}
