<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bio_link_id
 * @property string $title
 * @property string $url
 * @property string|null $icon
 * @property int $sort_order
 * @property bool $is_active
 * @property int $click_count
 */
class BioLinkItem extends Model
{
    protected $guarded = ['id'];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'click_count' => 'integer',
        ];
    }

    /** @return BelongsTo<BioLink, $this> */
    public function bioLink(): BelongsTo
    {
        return $this->belongsTo(BioLink::class);
    }
}
