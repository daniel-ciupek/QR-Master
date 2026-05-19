<?php

declare(strict_types=1);

namespace App\Enums;

enum FrameType: string
{
    case None = 'none';
    case Simple = 'simple';
    case Rounded = 'rounded';
    case SpeechBubble = 'speech-bubble';
}
