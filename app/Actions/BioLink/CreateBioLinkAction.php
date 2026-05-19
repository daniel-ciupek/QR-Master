<?php

declare(strict_types=1);

namespace App\Actions\BioLink;

use App\Enums\BioLinkTemplate;
use App\Models\BioLink;
use App\Models\QrCode;
use App\Models\User;

final class CreateBioLinkAction
{
    public function handle(User $user, QrCode $qrCode): BioLink
    {
        return BioLink::create([
            'user_id' => $user->id,
            'qr_code_id' => $qrCode->id,
            'title' => $qrCode->title,
            'bio' => null,
            'template' => BioLinkTemplate::Minimal,
            'theme' => BioLink::defaultTheme(),
        ]);
    }
}
