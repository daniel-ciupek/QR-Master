<?php

declare(strict_types=1);

namespace App\Actions\QrCode\Ai;

use App\Models\User;
use App\Services\AiService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class SemanticSearchAction
{
    public function __construct(private readonly AiService $ai) {}

    /**
     * @return Collection<int, object{id: int, title: string, type: string, short_hash: string, similarity: float}>
     */
    public function handle(User $user, string $query, int $limit = 10): Collection
    {
        $embedding = Cache::remember(
            'search-embed:'.md5($query),
            3600,
            fn () => $this->ai->generateEmbedding($query)
        );

        if ($embedding === []) {
            return collect();
        }

        $vector = '['.implode(',', $embedding).']';

        $results = DB::select('
            SELECT id, title, type, short_hash,
                   1 - (embedding <=> ?::vector) AS similarity
            FROM qr_codes
            WHERE user_id = ?
              AND embedding IS NOT NULL
              AND deleted_at IS NULL
            ORDER BY embedding <=> ?::vector
            LIMIT ?
        ', [$vector, $user->id, $vector, $limit]);

        return collect($results)->filter(fn ($r) => (float) $r->similarity > 0.5);
    }
}
