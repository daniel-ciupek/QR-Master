<?php

declare(strict_types=1);

namespace App\Enums;

enum BioLinkTemplate: string
{
    case Minimal = 'minimal';
    case Bold = 'bold';
    case Glassmorphism = 'glassmorphism';
    case Retro = 'retro';
}
