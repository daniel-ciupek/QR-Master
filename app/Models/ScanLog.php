<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $qr_code_id
 * @property string $ip_hash
 * @property string|null $country
 * @property string|null $region
 * @property string|null $city
 * @property float|null $lat
 * @property float|null $lng
 * @property string|null $device_type
 * @property string|null $os
 * @property string|null $browser
 * @property string|null $referrer
 * @property string|null $language
 * @property Carbon $scanned_at
 */
class ScanLog extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'scanned_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<QrCode, $this> */
    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }
}
