<?php

namespace App\Services\Squad;

use App\Enums\LeaguePhase;
use App\Enums\UserType;
use App\Exceptions\ApiException;
use App\Http\Resources\Squad\SquadCollection;
use App\Http\Resources\Squad\SquadResource;
use App\Models\LeagueCategoryPrice;
use App\Models\Season;
use App\Models\Squad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SquadService
{
    public function index(array $data): SquadCollection
    {
        $actor = Auth::user();
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;
        $userId = $data['user_id'] ?? null;
        $leagueId = $actor->hasRole(UserType::SYSTEM_ADMIN->value) ? ($data['league_id'] ?? null) : null;

        $query = $this->baseQuery()
            ->when($search, fn (Builder $query) => $query->where('players.name', 'ILIKE', "%{$search}%"))
            ->when($userId, fn (Builder $query) => $query->where('squads.user_id', $userId))
            ->when($leagueId, fn (Builder $query) => $query->where('squads.league_id', $leagueId))
            ->orderBy('players.name');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return new SquadCollection($paginator);
    }

    public function show(array $data): SquadResource
    {
        $squad = $this->baseQuery()->where('squads.id', $data['id'])->firstOrFail();

        return new SquadResource($squad);
    }

    public function adjustSalary(array $data): SquadResource
    {
        return DB::transaction(function () use ($data): SquadResource {
            $squad = Squad::with('user')->findOrFail($data['id']);
            $actor = Auth::user();

            $isOwner = $squad->user_id === $actor->id;
            $isSystemAdmin = $actor->hasRole(UserType::SYSTEM_ADMIN->value);

            if (! $isOwner && ! $isSystemAdmin) {
                throw new ApiException('Apenas o dono do elenco pode reajustar este salário.', 403);
            }

            $season = Season::currentFor($squad->league_id);

            if ($season === null || $season->phase !== LeaguePhase::WindowOpening) {
                throw new ApiException('O reajuste de salário só é permitido durante a Janela Inicial.', 409);
            }

            $newSalary = (string) $data['salary'];

            $otherSquadsTotal = Squad::query()
                ->where('user_id', $squad->user_id)
                ->where('league_id', $squad->league_id)
                ->where('id', '!=', $squad->id)
                ->sum('salary');

            $projectedTotal = bcadd((string) $otherSquadsTotal, $newSalary, 2);

            if (bccomp($projectedTotal, (string) $squad->user->balance, 2) === 1) {
                throw new ApiException('O reajuste excede o saldo disponível do usuário.', 422);
            }

            $squad->update(['salary' => $newSalary]);

            return $this->show(['id' => $squad->id]);
        });
    }

    private function baseQuery(): Builder
    {
        $query = Squad::query()
            ->select([
                'squads.*',
                'players.name as player_name',
                'players.overall as player_overall',
                'players.position as player_position',
                'players.image_path as player_image_path',
                'league_category_prices.category as player_category',
                'users.username as owner_username',
            ])
            ->join('players', 'players.id', '=', 'squads.player_id')
            ->join('users', 'users.id', '=', 'squads.user_id');

        LeagueCategoryPrice::applyBestMatchJoin($query, leagueIdColumn: 'squads.league_id');

        return $query;
    }
}
