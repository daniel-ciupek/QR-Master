<?php

declare(strict_types=1);

namespace App\Actions\QrCode\Ai;

use App\Models\QrCode;
use App\Services\AiService;

final class SuggestQrNameAction
{
    public function __construct(private readonly AiService $ai) {}

    /** @return array{name: string} */
    public function handle(QrCode $qrCode): array
    {
        $input = $qrCode->destination_url ?? $qrCode->type->value;

        return $this->ai->suggestQrName($input);
    }
}
