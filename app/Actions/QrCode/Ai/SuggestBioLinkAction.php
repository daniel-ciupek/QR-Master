<?php

declare(strict_types=1);

namespace App\Actions\QrCode\Ai;

use App\Services\AiService;

final class SuggestBioLinkAction
{
    public function __construct(private readonly AiService $ai) {}

    /** @return array{bio: string, emoji: string, link_suggestions: list<string>} */
    public function handle(string $professionOrIndustry): array
    {
        return $this->ai->suggestBioLinkContent($professionOrIndustry);
    }
}
