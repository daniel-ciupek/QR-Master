<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $session_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $last_active_at
 * @property Carbon $created_at
 */
class UserSession extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'session_id', 'ip_address', 'user_agent', 'last_active_at'];

    protected $casts = ['last_active_at' => 'datetime', 'created_at' => 'datetime'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parsedBrowser(): string
    {
        $ua = $this->user_agent ?? '';

        return match (true) {
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'OPR') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'Unknown Browser',
        };
    }

    public function parsedOs(): string
    {
        $ua = $this->user_agent ?? '';

        return match (true) {
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Unknown OS',
        };
    }
}
