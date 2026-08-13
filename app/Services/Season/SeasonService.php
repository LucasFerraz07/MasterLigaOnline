<?php

namespace App\Services\Season;

use App\Enums\LeaguePhase;
use App\Enums\SeasonStatus;
use App\Enums\UserType;
use App\Exceptions\ApiException;
use App\Http\Resources\Season\SeasonCollection;
use App\Http\Resources\Season\SeasonResource;
use App\Models\Season;
use App\Services\Game\GameScheduleService;
use App\Services\Squad\SquadLimitService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SeasonService
{
    public function __construct(
        private readonly SquadLimitService $squadLimitService,
        private readonly GameScheduleService $gameScheduleService
    ) {}

    public function index(array $data): SeasonCollection
    {
        $actor = Auth::user();
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $leagueId = $actor->user_type === 'system_admin' ? $data['league_id'] : null;

        $query = Season::query()
            ->when($leagueId, fn ($query) => $query->where('league_id', $leagueId))
            ->orderByDesc('season_number');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return new SeasonCollection($paginator);
    }

    public function show(array $data): SeasonResource
    {
        $season = Season::findOrFail($data['id']);

        return new SeasonResource($season);
    }

    public function store(array $data): SeasonResource
    {
        return DB::transaction(function () use ($data): SeasonResource {
            $actor = Auth::user();
            $leagueId = $actor->user_type === UserType::SYSTEM_ADMIN
                ? $data['league_id']
                : $actor->league_id;

            $hasOngoingSeason = Season::where('league_id', $leagueId)
                ->where('status', '!=', SeasonStatus::Closed->value)
                ->exists();

            if ($hasOngoingSeason) {
                throw new ApiException('Já existe uma temporada em andamento nesta liga. Encerre-a antes de iniciar uma nova.', 409);
            }

            $lastSeasonNumber = Season::where('league_id', $leagueId)->max('season_number');

            $season = Season::create([
                'league_id' => $leagueId,
                'season_number' => ($lastSeasonNumber ?? 0) + 1,
                'start_date' => now(),
                'status' => SeasonStatus::Open,
                'phase' => LeaguePhase::WindowOpening,
            ]);

            return new SeasonResource($season);
        });
    }

    public function advancePhase(array $data): SeasonResource
    {
        return DB::transaction(function () use ($data): SeasonResource {
            $season = Season::query()->lockForUpdate()->findOrFail($data['id']);

            if ($season->status === SeasonStatus::Closed) {
                throw new ApiException('Esta temporada já foi encerrada.', 409);
            }

            if ($season->phase === LeaguePhase::FirstWindow) {
                $league = $season->league()->lockForUpdate()->firstOrFail();
                $this->squadLimitService->enforceForLeague($league);
                $this->gameScheduleService->generateForSeason($season, $league);
            }

            [$phase, $status] = match ($season->phase) {
                LeaguePhase::WindowOpening => [LeaguePhase::FirstWindow, SeasonStatus::Open],
                LeaguePhase::FirstWindow => [LeaguePhase::FirstHalf, SeasonStatus::Active],
                LeaguePhase::FirstHalf => [LeaguePhase::MidWindow, SeasonStatus::Open],
                LeaguePhase::MidWindow => [LeaguePhase::SecondHalf, SeasonStatus::Active],
                LeaguePhase::SecondHalf => [LeaguePhase::Ended, SeasonStatus::Closed],
            };

            $season->update([
                'phase' => $phase,
                'status' => $status,
            ]);

            return new SeasonResource($season);
        });
    }
}
