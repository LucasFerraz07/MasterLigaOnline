<?php

namespace App\Models;

use App\Enums\BidStatus;
use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperTransferBid
 */
class TransferBid extends Model
{
    use HasUuids, Tenantable;

    protected $fillable = [
        'league_id',
        'season_id',
        'proposer_id',
        'receiver_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => BidStatus::class,
        ];
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposer_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransferBidItem::class, 'bid_id');
    }
}
