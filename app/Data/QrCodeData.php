<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\QrCodeType;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class QrCodeData extends Data
{
    public function __construct(
        #[Required, Max(255)]
        public readonly string $title,

        public readonly QrCodeType $type,

        #[Max(900)]
        public readonly ?string $destination_url,

        /** @var array<string, mixed> */
        public readonly array $settings = [],

        public readonly bool $is_active = true,

        public readonly ?Carbon $expires_at = null,

        public readonly ?Carbon $activates_at = null,

        public readonly ?string $password = null,
    ) {}
}
