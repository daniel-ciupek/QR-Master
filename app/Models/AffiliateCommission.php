<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $referred_user_id
 * @property int $amount_gross
 * @property int $commission_amount
 * @property string $currency
 * @property string|null $stripe_invoice_id
 * @property string $status
 */
class AffiliateCommission extends Model
{
    protected $fillable = [
        'user_id',
        'referred_user_id',
        'amount_gross',
        'commission_amount',
        'currency',
        'stripe_invoice_id',
        'status',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
