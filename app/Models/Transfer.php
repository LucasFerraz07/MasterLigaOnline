<?php

namespace App\Models;

use App\Enums\TransferType;
use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperTransfer
 */
class Transfer extends Model
{
    use HasUuids, Tenantable;

    protected $fillable = [
        'league_id',
        'bid_id',
        'player_id',
        'seller_id',
        'buyer_id',
        'season_id',
        'type',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransferType::class,
            'amount' => 'decimal:2',
        ];
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}