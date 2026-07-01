<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    protected function casts(): array
    {
        return [
            'subscription_start'  => 'date',
            'subscription_end'    => 'date',
            'silver_limit'        => 'integer',
            'golden_limit'        => 'integer',
            'black_limit'         => 'integer',
            'mulct_contract_limit' => 'integer',
            'player_limit'        => 'integer',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
