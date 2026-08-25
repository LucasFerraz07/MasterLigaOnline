<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperPlan
 */
class Plan extends Model
{
    use HasUuids;

    protected $fillable = [
        'code',
        'name',
        'user_limit',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'user_limit' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(PlanPrice::class);
    }
}
