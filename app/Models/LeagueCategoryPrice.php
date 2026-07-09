<?php

namespace App\Models;

use App\Enums\Category;
use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperLeagueCategoryPrice
 */
class LeagueCategoryPrice extends Model
{
    use HasUuids, Tenantable;

    protected $fillable = [
        'league_id',
        'category',
        'base_salary',
    ];

    protected $appends = [
        'base_passe',
    ];

    protected function casts(): array
    {
        return [
            'category' => Category::class,
            'base_salary' => 'decimal:2',
        ];
    }

    public function getBasePasseAttribute(): string
    {
        return bcmul((string) $this->base_salary, '10', 2);
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }
}
