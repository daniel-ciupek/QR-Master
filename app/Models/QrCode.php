<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\QrVisualSettings;
use App\Enums\QrCodeType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int $id
 * @property int $user_id
 * @property QrCodeType $type
 * @property string $title
 * @property string $slug
 * @property string $short_hash
 * @property string|null $destination_url
 * @property array<string, mixed> $settings
 * @property bool $is_active
 * @property Carbon|null $expires_at
 * @property string|null $password_hash
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class QrCode extends Model implements HasMedia
{
    use HasSlug;
    use InteractsWithMedia;
    use MassPrunable;
    use SoftDeletes;

    protected $guarded = ['id'];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'type' => QrCodeType::class,
            'settings' => 'array',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
            // PII — encrypted at rest
            'vcard_phone' => 'encrypted',
            'vcard_email' => 'encrypted',
            'wifi_password' => 'encrypted',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(100);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp']);

        $this->addMediaCollection('pdf_menu')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200);
    }

    // ── Pruning ────────────────────────────────────────────────────────

    /** @return Builder<QrCode> */
    public function prunable(): Builder
    {
        return static::withTrashed()
            ->whereNotNull('deleted_at')
            ->where('deleted_at', '<=', now()->subDays(30));
    }

    // ── Relations ──────────────────────────────────────────────────────

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<ScanLog, $this> */
    public function scanLogs(): HasMany
    {
        return $this->hasMany(ScanLog::class);
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────

    /** @param Builder<QrCode> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /** @param Builder<QrCode> $query */
    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    // ── Helpers ────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isPasswordProtected(): bool
    {
        return $this->password_hash !== null;
    }

    public function visualSettings(): QrVisualSettings
    {
        return QrVisualSettings::from($this->settings ?? []);
    }
}
