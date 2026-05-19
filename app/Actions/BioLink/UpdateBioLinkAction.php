<?php

declare(strict_types=1);

namespace App\Actions\BioLink;

use App\Enums\BioLinkTemplate;
use App\Models\BioLink;

final class UpdateBioLinkAction
{
    /** @param array<string, mixed> $theme */
    public function handle(
        BioLink $bioLink,
        ?string $bio,
        BioLinkTemplate $template,
        array $theme,
    ): void {
        $bioLink->update([
            'bio' => $bio,
            'template' => $template,
            'theme' => array_merge(BioLink::defaultTheme(), $theme),
        ]);
    }
}
