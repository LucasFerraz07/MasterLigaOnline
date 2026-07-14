<?php

namespace App\Models;

use App\Enums\TransferItemType;
use App\Enums\TransferSide;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperTransferBidItem
 */
class TransferBidItem extends Model
{
    protected $fillable = [
        'bid_id',
        'side',
        'item_type',
        'player_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'side' => TransferSide::class,
            'item_type' => TransferItemType::class,
            'amount' => 'decimal:2',
        ];
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(TransferBid::class, 'bid_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
