<?php

namespace App\Models;

use App\Enums\Category;
use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

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
        'min_overall',
    ];

    protected $appends = [
        'base_passe',
    ];

    protected function casts(): array
    {
        return [
            'category' => Category::class,
            'base_salary' => 'decimal:2',
            'min_overall' => 'integer',
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

    /**
     * Junta players -> league_category_prices pegando, para cada player,
     * a faixa com o maior min_overall que ainda seja <= players.overall
     * (piso de overall configurável por liga).
     *
     * Informe $leagueId para um valor fixo (ex.: liga do usuário autenticado)
     * ou $leagueIdColumn para casar dinamicamente por linha (ex.: 'squads.league_id',
     * quando a query pode abranger mais de uma liga na mesma consulta).
     */
    public static function applyBestMatchJoin(Builder $query, ?string $leagueId = null, ?string $leagueIdColumn = null): Builder
    {
        return $query->leftJoin('league_category_prices', function ($join) use ($leagueId, $leagueIdColumn): void {
            $leagueIdColumn !== null
                ? $join->whereColumn('league_category_prices.league_id', $leagueIdColumn)
                : $join->where('league_category_prices.league_id', $leagueId);

            $join->whereColumn('league_category_prices.min_overall', '<=', 'players.overall')
                ->whereNotExists(function ($subQuery) use ($leagueId, $leagueIdColumn): void {
                    $subQuery->select(DB::raw(1))
                        ->from('league_category_prices as lcp2')
                        ->whereColumn('lcp2.min_overall', '<=', 'players.overall')
                        ->whereColumn('lcp2.min_overall', '>', 'league_category_prices.min_overall');

                    $leagueIdColumn !== null
                        ? $subQuery->whereColumn('lcp2.league_id', $leagueIdColumn)
                        : $subQuery->where('lcp2.league_id', $leagueId);
                });
        });
    }
}
