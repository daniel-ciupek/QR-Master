<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $ab_test_id
 * @property string $name
 * @property string $destination_url
 * @property int $weight
 * @property int $scan_count
 * @property bool $is_winner
 */
class AbVariant extends Model
{
    protected $guarded = ['id'];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'weight' => 'integer',
            'scan_count' => 'integer',
            'is_winner' => 'boolean',
        ];
    }

    /** @return BelongsTo<AbTest, $this> */
    public function abTest(): BelongsTo
    {
        return $this->belongsTo(AbTest::class);
    }
}
