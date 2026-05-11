<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\CornerDotStyle;
use App\Enums\CornerSquareStyle;
use App\Enums\DotStyle;
use App\Enums\FrameType;
use App\Enums\GradientType;
use Spatie\LaravelData\Data;

final class QrVisualSettings extends Data
{
    public function __construct(
        public readonly DotStyle $dotStyle = DotStyle::Square,
        public readonly string $dotColor = '#000000',
        public readonly string $bgColor = '#ffffff',
        public readonly CornerSquareStyle $cornerSquareStyle = CornerSquareStyle::Square,
        public readonly CornerDotStyle $cornerDotStyle = CornerDotStyle::Square,
        public readonly bool $gradientEnabled = false,
        public readonly GradientType $gradientType = GradientType::Linear,
        public readonly string $gradientColorStart = '#000000',
        public readonly string $gradientColorEnd = '#444444',
        public readonly int $gradientRotation = 0,
        public readonly ?string $logoPath = null,
        public readonly int $logoMargin = 5,
        public readonly float $logoSize = 0.3,
        public readonly bool $logoHideBackground = false,
        public readonly FrameType $frame = FrameType::None,
        public readonly string $frameText = '',
        public readonly string $frameColor = '#000000',
    ) {}
}
