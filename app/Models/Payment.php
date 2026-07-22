<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperPayment
 */
class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'league_id',
        'months',
        'league_name',
        'owner_full_name',
        'amount',
        'status',
        'gateway',
        'external_id',
        'pix_qr_code',
        'pix_br_code',
        'expires_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'months' => 'integer',
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function subscriptionPeriods(): HasMany
    {
        return $this->hasMany(SubscriptionPeriod::class);
    }
}
