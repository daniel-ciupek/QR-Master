<?php

declare(strict_types=1);

namespace App\Data\Team;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class UpdateTeamData extends Data
{
    public function __construct(
        #[Required, Max(60)]
        public readonly string $name,
    ) {}
}
