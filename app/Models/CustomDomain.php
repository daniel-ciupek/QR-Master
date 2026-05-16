<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $domain
 * @property string $status
 * @property string $verification_token
 * @property Carbon|null $verified_at
 * @property string $ssl_status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class CustomDomain extends Model
{
    protected $fillable = [
        'user_id',
        'domain',
        'status',
        'verification_token',
        'verified_at',
        'ssl_status',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }
}
