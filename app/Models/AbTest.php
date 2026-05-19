<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $qr_code_id
 * @property bool $is_active
 * @property bool $auto_select_winner
 * @property int $auto_select_threshold
 */
class AbTest extends Model
{
    protected $guarded = ['id'];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'auto_select_winner' => 'boolean',
            'auto_select_threshold' => 'integer',
        ];
    }

    /** @return BelongsTo<QrCode, $this> */
    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }

    /** @return HasMany<AbVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(AbVariant::class);
    }

    public function hasWinner(): bool
    {
        return $this->variants()->where('is_winner', true)->exists();
    }
}
