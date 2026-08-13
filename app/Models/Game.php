<?php

namespace App\Models;

use App\Enums\MatchStatus;
use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperGame
 */
class Game extends Model
{
    use HasUuids, Tenantable;

    protected $table = 'matches';

    protected $fillable = [
        'league_id',
        'season_id',
        'home_user_id',
        'away_user_id',
        'home_goals',
        'away_goals',
        'round',
        'half',
        'is_bye',
        'match_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'home_goals' => 'integer',
            'away_goals' => 'integer',
            'round' => 'integer',
            'half' => 'integer',
            'is_bye' => 'boolean',
            'match_date' => 'date',
            'status' => MatchStatus::class,
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

    public function homeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'home_user_id');
    }

    public function awayUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'away_user_id');
    }
}
