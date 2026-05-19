<?php

declare(strict_types=1);

namespace App\Actions\BioLink;

use App\Models\BioLinkItem;

final class UpdateBioLinkItemAction
{
    public function handle(BioLinkItem $item, string $title, string $url, ?string $icon, bool $isActive): void
    {
        $item->update([
            'title' => $title,
            'url' => $url,
            'icon' => $icon,
            'is_active' => $isActive,
        ]);
    }
}
