<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamRole;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $name
 * @property string $slug
 * @property array<string, mixed> $settings
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class Team extends Model
{
    use HasSlug;
    use SoftDeletes;

    protected $guarded = ['id'];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(60);
    }

    // ── Relations ──────────────────────────────────────────────────────

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'joined_at'])
            ->withCasts(['role' => TeamRole::class])
            ->orderByPivot('joined_at');
    }

    /** @return HasMany<QrCode, $this> */
    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    /** @return HasMany<Tag, $this> */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /** @return HasMany<TeamInvitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /** @return HasMany<TeamInvitation, $this> */
    public function pendingInvitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now());
    }

    // ── Helpers ────────────────────────────────────────────────────────

    public function userRole(User $user): ?TeamRole
    {
        $member = $this->members->find($user->id);

        if ($member === null) {
            return null;
        }

        /** @phpstan-ignore-next-line property.notFound */
        return $member->pivot->role;
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }
}
