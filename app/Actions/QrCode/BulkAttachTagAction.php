<?php

declare(strict_types=1);

namespace App\Actions\QrCode;

use App\Models\QrCode;
use App\Models\Tag;
use App\Models\User;

final class BulkAttachTagAction
{
    /** @param array<int> $qrCodeIds */
    public function handle(User $user, array $qrCodeIds, int $tagId): void
    {
        $tag = Tag::where('user_id', $user->id)->where('id', $tagId)->first();
        if ($tag === null) {
            return;
        }

        QrCode::forUser($user->id)
            ->with('tags')
            ->whereIn('id', $qrCodeIds)
            ->get()
            ->each(fn (QrCode $qr) => $qr->tags()->syncWithoutDetaching([$tagId]));
    }
}
