<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperSubscription
 */
class Subscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'user_limit',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price'      => 'decimal:2',
            'user_limit' => 'integer',
        ];
    }

    public function leagues(): HasMany
    {
        return $this->hasMany(League::class);
    }
}
