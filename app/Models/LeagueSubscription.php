<?php

namespace App\Models;

use App\Enums\LeagueSubscriptionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeagueSubscription extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'league_id', 'current_plan_id', 'current_plan_price_id', 'status', 'collection_mode', 'gateway', 'external_subscription_id', 'current_period_starts_at', 'current_period_ends_at', 'access_expires_at', 'next_billing_at', 'cancel_at_period_end'];

    protected function casts(): array
    {
        return ['status' => LeagueSubscriptionStatus::class, 'current_period_starts_at' => 'datetime', 'current_period_ends_at' => 'datetime', 'access_expires_at' => 'datetime', 'next_billing_at' => 'datetime', 'cancel_at_period_end' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function currentPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'current_plan_id');
    }

    public function currentPlanPrice(): BelongsTo
    {
        return $this->belongsTo(PlanPrice::class, 'current_plan_price_id');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(SubscriptionPeriod::class);
    }
}
