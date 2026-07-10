<?php

namespace App\Models;

use App\Enums\LeaguePhase;
use App\Enums\SeasonStatus;
use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperSeason
 */
class Season extends Model
{
    use HasUuids, Tenantable;

    protected $fillable = [
        'league_id',
        'season_number',
        'start_date',
        'end_date',
        'status',
        'phase',
    ];

    protected function casts(): array
    {
        return [
            'season_number' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => SeasonStatus::class,
            'phase' => LeaguePhase::class,
        ];
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }
}
