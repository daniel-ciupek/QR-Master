<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $team_id
 * @property string $provider
 * @property string $email_domain
 * @property string $client_id
 * @property string $client_secret
 * @property string|null $tenant_id
 * @property string|null $okta_domain
 * @property bool $is_active
 * @property bool $auto_provision
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SsoConnection extends Model
{
    protected $guarded = ['id'];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'client_id' => 'encrypted',
            'client_secret' => 'encrypted',
            'is_active' => 'boolean',
            'auto_provision' => 'boolean',
        ];
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public static function findByEmail(string $email): ?self
    {
        $domain = strtolower(substr($email, strpos($email, '@') + 1));

        return self::where('email_domain', $domain)
            ->where('is_active', true)
            ->first();
    }
}
