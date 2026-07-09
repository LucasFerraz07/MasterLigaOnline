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
        'subscription_id',
        'subscription_start',
        'subscription_end',
    ];

    protected $appends = [
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'subscription_start' => 'date',
            'subscription_end' => 'date',
            'silver_limit' => 'integer',
            'golden_limit' => 'integer',
            'black_limit' => 'integer',
            'mulct_contract_limit' => 'integer',
            'player_limit' => 'integer',
        ];
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->deleted_at === null && $this->subscription_end->isFuture();
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function owners(): HasOne
    {
        return $this->hasOne(Owner::class);
    }

    public function categoryPrices(): HasMany
    {
        return $this->hasMany(LeagueCategoryPrice::class);
    }

    public function squads(): HasMany
    {
        return $this->hasMany(Squad::class);
    }
}
