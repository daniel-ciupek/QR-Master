<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stub — full implementation in Etap 5.
 *
 * @property int $id
 * @property int $qr_code_id
 */
class ScanLog extends Model
{
    protected $guarded = [];

    /** @return BelongsTo<QrCode, $this> */
    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }
}
