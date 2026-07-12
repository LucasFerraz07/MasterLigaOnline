<?php

namespace App\Models;

use App\Enums\AcquisitionType;
use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperSquad
 */
class Squad extends Model
{
    use HasUuids, Tenantable;

    protected $fillable = [
        'league_id',
        'user_id',
        'player_id',
        'acquisition_type',
        'salary',
    ];

    protected $appends = [
        'passe',
    ];

    protected function casts(): array
    {
        return [
            'salary' => 'decimal:2',
            'acquisition_type' => AcquisitionType::class,
        ];
    }

    public function getPasseAttribute(): string
    {
        return bcmul((string) $this->salary, '10', 2);
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
