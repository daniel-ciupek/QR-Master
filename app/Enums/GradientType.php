<?php

declare(strict_types=1);

namespace App\Enums;

enum GradientType: string
{
    case Linear = 'linear';
    case Radial = 'radial';
}
