<?php

namespace App\Models;

use App\Enums\SubscriptionPeriodStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperSubscriptionPeriod
 */
class SubscriptionPeriod extends Model
{
    use HasUuids;

    protected $fillable = [
        'league_id',
        'payment_id',
        'subscription_id',
        'months',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'months' => 'integer',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'status' => SubscriptionPeriodStatus::class,
        ];
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
