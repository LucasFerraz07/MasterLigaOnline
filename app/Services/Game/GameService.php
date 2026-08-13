<?php

namespace App\Services\Game;

use App\Enums\LeaguePhase;
use App\Enums\MatchStatus;
use App\Enums\UserType;
use App\Exceptions\ApiException;
use App\Http\Resources\Game\GameCollection;
use App\Http\Resources\Game\GameResource;
use App\Models\Game;
use App\Models\Season;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GameService
{
    public function index(array $data): GameCollection
    {
        $actor = Auth::user();
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $leagueId = null;

        if ($actor->user_type === UserType::SYSTEM_ADMIN) {
            $leagueId = $data['league_id'] ?? null;

            if ($leagueId === null) {
                throw new ApiException('league_id é obrigatório para system_admin consultar partidas.', 422);
            }
        }

        $query = $this->baseQuery()
            ->when($leagueId, fn (Builder $query) => $query->where('matches.league_id', $leagueId))
            ->when($data['season_id'] ?? null, fn (Builder $query, string $seasonId) => $query->where('matches.season_id', $seasonId))
            ->when($data['half'] ?? null, fn (Builder $query, int $half) => $query->where('matches.half', $half))
            ->when($data['round'] ?? null, fn (Builder $query, int $round) => $query->where('matches.round', $round))
            ->when($data['status'] ?? null, fn (Builder $query, string $status) => $query->where('matches.status', $status))
            ->when($data['user_id'] ?? null, function (Builder $query, string $userId): void {
                $query->where(function (Builder $query) use ($userId): void {
                    $query->where('matches.home_user_id', $userId)
                        ->orWhere('matches.away_user_id', $userId);
                });
            })
            ->orderBy('matches.half')
            ->orderBy('matches.round')
            ->orderBy('matches.is_bye')
            ->orderBy('matches.home_user_id');

        return new GameCollection($query->paginate($perPage, ['*'], 'page', $page));
    }

    public function show(array $data): GameResource
    {
        return new GameResource($this->baseQuery()->where('matches.id', $data['id'])->firstOrFail());
    }

    public function publishResult(array $data): GameResource
    {
        return DB::transaction(function () use ($data): GameResource {
            $game = Game::query()->lockForUpdate()->findOrFail($data['id']);

            if ($game->is_bye) {
                throw new ApiException('Não é possível publicar ou editar o resultado de uma folga.', 422);
            }

            $actor = Auth::user();
            $isParticipant = in_array($actor->id, [$game->home_user_id, $game->away_user_id], true);
            $isLeagueAdmin = $actor->user_type === UserType::LEAGUE_ADMIN;

            if (! $isParticipant && ! $isLeagueAdmin) {
                throw new ApiException('Apenas os participantes da partida ou o league_admin podem publicar este resultado.', 403);
            }

            $season = Season::query()->lockForUpdate()->findOrFail($game->season_id);
            $activeHalf = match ($season->phase) {
                LeaguePhase::FirstHalf => 1,
                LeaguePhase::SecondHalf => 2,
                default => null,
            };

            if ($activeHalf !== $game->half) {
                throw new ApiException('O resultado só pode ser publicado ou editado durante o turno correspondente da temporada.', 409);
            }

            $game->update([
                'home_goals' => $data['home_goals'],
                'away_goals' => $data['away_goals'],
                'status' => MatchStatus::Finished,
            ]);

            return $this->show(['id' => $game->id]);
        });
    }

    private function baseQuery(): Builder
    {
        return Game::query()
            ->with(['homeUser', 'awayUser']);
    }
}
