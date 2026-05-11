<?php

declare(strict_types=1);

namespace App\Enums;

enum DotStyle: string
{
    case Square = 'square';
    case Dots = 'dots';
    case Rounded = 'rounded';
    case Classy = 'classy';
    case ClassyRounded = 'classy-rounded';
    case ExtraRounded = 'extra-rounded';
}
