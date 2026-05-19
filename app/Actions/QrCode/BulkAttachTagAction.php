<?php

declare(strict_types=1);

namespace App\Actions\QrCode;

use App\Models\QrCode;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class BulkAttachTagAction
{
    /** @param array<int> $qrCodeIds */
    public function handle(User $user, array $qrCodeIds, int $tagId): void
    {
        $tag = Tag::where('user_id', $user->id)->where('id', $tagId)->first();
        if ($tag === null) {
            return;
        }

        $validIds = QrCode::forUser($user->id)
            ->whereIn('id', $qrCodeIds)
            ->pluck('id');

        if ($validIds->isEmpty()) {
            return;
        }

        $rows = $validIds->map(fn (int $id) => ['qr_code_id' => $id, 'tag_id' => $tagId])->all();

        DB::table('qr_code_tag')->insertOrIgnore($rows);
    }
}
