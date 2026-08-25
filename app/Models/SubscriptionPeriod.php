<?php

namespace App\Models;

use App\Enums\SubscriptionPeriodStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPeriod extends Model
{
    use HasUuids;

    protected $fillable = ['league_subscription_id', 'plan_id', 'plan_price_id', 'payment_id', 'amount_cents', 'currency', 'starts_at', 'ends_at', 'status'];

    protected function casts(): array
    {
        return ['amount_cents' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'status' => SubscriptionPeriodStatus::class];
    }

    public function leagueSubscription(): BelongsTo
    {
        return $this->belongsTo(LeagueSubscription::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function planPrice(): BelongsTo
    {
        return $this->belongsTo(PlanPrice::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
