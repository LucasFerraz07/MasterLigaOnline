<?php

namespace App\Models;

use App\Enums\CheckoutStatus;
use App\Enums\CheckoutType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Checkout extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'league_id', 'plan_price_id', 'type', 'status', 'league_name', 'owner_full_name', 'amount_cents', 'currency', 'client_idempotency_key', 'expires_at', 'paid_at', 'fulfilled_at'];

    protected function casts(): array
    {
        return ['type' => CheckoutType::class, 'status' => CheckoutStatus::class, 'amount_cents' => 'integer', 'expires_at' => 'datetime', 'paid_at' => 'datetime', 'fulfilled_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function planPrice(): BelongsTo
    {
        return $this->belongsTo(PlanPrice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
