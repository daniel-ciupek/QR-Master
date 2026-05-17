<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $severity
 * @property string $status
 * @property bool $is_maintenance
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class StatusIncident extends Model
{
    protected $guarded = ['id'];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'is_maintenance' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->ends_at === null || $this->ends_at->isFuture();
    }
}
