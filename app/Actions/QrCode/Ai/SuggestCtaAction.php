<?php

declare(strict_types=1);

namespace App\Actions\QrCode\Ai;

use App\Services\AiService;

final class SuggestCtaAction
{
    public function __construct(private readonly AiService $ai) {}

    /** @return array{suggestions: list<string>} */
    public function handle(string $context): array
    {
        return $this->ai->suggestCta($context);
    }
}
