<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BioLinkTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int $id
 * @property int $user_id
 * @property int $qr_code_id
 * @property string $slug
 * @property string $title
 * @property string|null $bio
 * @property BioLinkTemplate $template
 * @property array<string, mixed> $theme
 */
class BioLink extends Model implements HasMedia
{
    use HasSlug;
    use InteractsWithMedia;

    protected $guarded = ['id'];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'template' => BioLinkTemplate::class,
            'theme' => 'array',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(80);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp', 'image/gif']);
    }

    // ── Relations ──────────────────────────────────────────────────────

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<QrCode, $this> */
    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }

    /** @return HasMany<BioLinkItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(BioLinkItem::class)->orderBy('sort_order');
    }

    // ── Helpers ────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function resolvedTheme(): array
    {
        return array_merge(self::defaultTheme(), $this->theme ?? []);
    }

    /** @return array<string, mixed> */
    public static function defaultTheme(): array
    {
        return [
            'primary_color' => '#6366f1',
            'bg_color' => '#ffffff',
            'text_color' => '#111827',
            'button_shape' => 'pill',
            'font' => 'inter',
        ];
    }
}
