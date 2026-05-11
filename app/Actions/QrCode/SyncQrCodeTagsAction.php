<?php

declare(strict_types=1);

namespace App\Actions\QrCode;

use App\Models\QrCode;
use App\Models\Tag;
use App\Models\User;

final class SyncQrCodeTagsAction
{
    /** @param array<int> $tagIds */
    public function handle(QrCode $qrCode, User $user, array $tagIds): void
    {
        $validIds = Tag::where('user_id', $user->id)
            ->whereIn('id', $tagIds)
            ->pluck('id')
            ->toArray();

        $qrCode->tags()->sync($validIds);
    }
}
