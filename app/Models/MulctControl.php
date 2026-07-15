<?php

namespace App\Models;

use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperMulctControl
 */
class MulctControl extends Model
{
    use HasUuids, Tenantable;

    protected $table = 'mulct_control';

    protected $fillable = [
        'league_id',
        'user_id',
        'season_id',
        'buyouts_purchased',
        'buyouts_lost',
    ];

    protected function casts(): array
    {
        return [
            'buyouts_purchased' => 'integer',
            'buyouts_lost' => 'integer',
        ];
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}
