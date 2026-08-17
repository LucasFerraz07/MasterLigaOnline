<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperLeague
 */
class League extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'silver_limit',
        'golden_limit',
        'black_limit',
        'mulct_contract_limit',
        'player_limit',
        'win_credit',
        'draw_credit',
        'loss_credit',
        'subscription_id',
        'subscription_start',
        'subscription_end',
        'deactivated_at',
    ];

    protected $appends = [
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'subscription_start' => 'date',
            'subscription_end' => 'date',
            'deactivated_at' => 'datetime',
            'silver_limit' => 'integer',
            'golden_limit' => 'integer',
            'black_limit' => 'integer',
            'mulct_contract_limit' => 'integer',
            'player_limit' => 'integer',
            'win_credit' => 'decimal:2',
            'draw_credit' => 'decimal:2',
            'loss_credit' => 'decimal:2',
        ];
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->deleted_at === null
            && $this->deactivated_at === null
            && $this->subscription_end->isFuture();
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function owners(): HasOne
    {
        return $this->hasOne(Owner::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function subscriptionPeriods(): HasMany
    {
        return $this->hasMany(SubscriptionPeriod::class);
    }

    public function categoryPrices(): HasMany
    {
        return $this->hasMany(LeagueCategoryPrice::class);
    }

    public function squads(): HasMany
    {
        return $this->hasMany(Squad::class);
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class);
    }

    public function clubIdentities(): HasMany
    {
        return $this->hasMany(ClubIdentity::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(Game::class);
    }
}
