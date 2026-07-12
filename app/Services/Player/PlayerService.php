<?php

namespace App\Services\Player;

use App\Enums\SeasonStatus;
use App\Http\Resources\Player\PlayerCollection;
use App\Http\Resources\Player\PlayerResource;
use App\Models\Player;
use App\Models\Season;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PlayerService
{
    public function index(array $data): PlayerCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;
        $category = $data['category'] ?? null;
        $minOverall = $data['min_overall'] ?? null;
        $maxOverall = $data['max_overall'] ?? null;
        $minSalary = $data['min_salary'] ?? null;
        $maxSalary = $data['max_salary'] ?? null;
        $minPasse = $data['min_passe'] ?? null;
        $maxPasse = $data['max_passe'] ?? null;

        $query = $this->baseQuery()
            ->when($category, fn (Builder $query) => $query->where('players.category', $category))
            ->when($search, function (Builder $query) use ($search): void {
                $query->where('players.name', 'ILIKE', "%{$search}%");
            })
            ->when($minOverall, fn (Builder $query) => $query->where('players.overall', '>=', $minOverall))
            ->when($maxOverall, fn (Builder $query) => $query->where('players.overall', '<=', $maxOverall))
            ->when($minSalary, fn (Builder $query) => $query->where(DB::raw('COALESCE(squads.salary, league_category_prices.base_salary)'), '>=', $minSalary))
            ->when($maxSalary, fn (Builder $query) => $query->where(DB::raw('COALESCE(squads.salary, league_category_prices.base_salary)'), '<=', $maxSalary))
            ->when($minPasse, fn (Builder $query) => $query->where(DB::raw('COALESCE(squads.salary, league_category_prices.base_salary)'), '>=', bcdiv((string) $minPasse, '10', 2)))
            ->when($maxPasse, fn (Builder $query) => $query->where(DB::raw('COALESCE(squads.salary, league_category_prices.base_salary)'), '<=', bcdiv((string) $maxPasse, '10', 2)))
            ->orderBy('players.name');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return new PlayerCollection($paginator);
    }

    public function show(array $data): PlayerResource
    {
        $player = $this->baseQuery()->where('players.id', $data['id'])->firstOrFail();

        return new PlayerResource($player);
    }

    public function uploadPlayerImage(array $data): PlayerResource
    {
        $player = Player::findOrFail($data['id']);

        if ($player->image_path !== null) {
            Storage::disk('public')->delete($player->image_path);
        }

        $player->image_path = $data['image']->store('images/players', 'public');
        $player->save();

        $player = $this->baseQuery()->where('players.id', $player->id)->firstOrFail();

        return new PlayerResource($player);
    }

    private function baseQuery(): Builder
    {
        $leagueId = Auth::user()?->league_id;
        $seasonId = $this->currentSeasonId($leagueId);

        return Player::query()
            ->select([
                'players.*',
                DB::raw('COALESCE(squads.salary, league_category_prices.base_salary) as salary'),
            ])
            ->leftJoin('league_category_prices', function ($join) use ($leagueId): void {
                $join->on('league_category_prices.category', '=', 'players.category')
                    ->where('league_category_prices.league_id', $leagueId);
            })
            ->leftJoin('squads', function ($join) use ($leagueId, $seasonId): void {
                $join->on('squads.player_id', '=', 'players.id')
                    ->where('squads.league_id', $leagueId)
                    ->where('squads.season_id', $seasonId);
            });
    }

    private function currentSeasonId(?string $leagueId): ?string
    {
        if (! $leagueId) {
            return null;
        }

        return Season::where('league_id', $leagueId)
            ->where('status', '!=', SeasonStatus::Closed->value)
            ->value('id');
    }
}
