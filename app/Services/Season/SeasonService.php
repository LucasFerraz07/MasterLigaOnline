<?php

namespace App\Services\Season;

use App\Enums\LeaguePhase;
use App\Enums\MatchStatus;
use App\Enums\SeasonStatus;
use App\Enums\UserType;
use App\Exceptions\ApiException;
use App\Http\Resources\Season\SeasonCollection;
use App\Http\Resources\Season\SeasonResource;
use App\Models\FinancialTransaction;
use App\Models\Game;
use App\Models\League;
use App\Models\Season;
use App\Models\Squad;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\Game\GameScheduleService;
use App\Services\Notification\NotificationService;
use App\Services\Squad\SquadLimitService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SeasonService
{
    public function __construct(
        private readonly SquadLimitService $squadLimitService,
        private readonly GameScheduleService $gameScheduleService,
        private readonly NotificationService $notificationService,
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

            $this->notifyLeagueUsers(
                season: $season,
                type: 'season_created',
                title: 'Nova temporada iniciada',
                body: "A temporada {$season->season_number} foi iniciada.",
            );

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

            if ($season->phase === LeaguePhase::FirstHalf) {
                $this->ensureHalfIsFinished($season, 1, 'avançar para a Janela Intermediária');
            }

            if ($season->phase === LeaguePhase::SecondHalf) {
                $this->ensureHalfIsFinished($season, 2, 'encerrar a temporada');
            }

            [$phase, $status] = match ($season->phase) {
                LeaguePhase::WindowOpening => [LeaguePhase::FirstWindow, SeasonStatus::Open],
                LeaguePhase::FirstWindow => [LeaguePhase::FirstHalf, SeasonStatus::Active],
                LeaguePhase::FirstHalf => [LeaguePhase::MidWindow, SeasonStatus::Open],
                LeaguePhase::MidWindow => [LeaguePhase::SecondHalf, SeasonStatus::Active],
                LeaguePhase::SecondHalf => [LeaguePhase::Ended, SeasonStatus::Closed],
            };

            if ($phase === LeaguePhase::Ended) {
                $league = $season->league()->lockForUpdate()->firstOrFail();
                $this->creditSeasonPerformance($season, $league);
                $this->debitSquadSalaries($season);
            }

            $season->update([
                'phase' => $phase,
                'status' => $status,
            ]);

            $this->notifyLeagueUsers(
                season: $season,
                type: 'season_phase_changed',
                title: 'Fase da temporada alterada',
                body: "A temporada {$season->season_number} avançou para a fase: {$this->phaseLabel($phase)}.",
            );

            return new SeasonResource($season);
        });
    }

    private function notifyLeagueUsers(Season $season, string $type, string $title, string $body): void
    {
        $userIds = User::withoutGlobalScopes()
            ->where('league_id', $season->league_id)
            ->pluck('id');

        $this->notificationService->createForUsers($userIds, $type, $title, $body);
    }

    private function phaseLabel(LeaguePhase $phase): string
    {
        return match ($phase) {
            LeaguePhase::WindowOpening => 'Janela de abertura',
            LeaguePhase::FirstWindow => 'Primeira janela de transferências',
            LeaguePhase::FirstHalf => 'Primeiro turno',
            LeaguePhase::MidWindow => 'Janela intermediária',
            LeaguePhase::SecondHalf => 'Segundo turno',
            LeaguePhase::Ended => 'Temporada encerrada',
        };
    }

    /**
     * Credita o desempenho consolidado de cada participante antes do débito salarial.
     */
    private function creditSeasonPerformance(Season $season, League $league): void
    {
        $resultsByUser = [];

        $recordResult = function (string $userId, string $result) use (&$resultsByUser): void {
            $resultsByUser[$userId] ??= ['wins' => 0, 'draws' => 0, 'losses' => 0];
            $resultsByUser[$userId][$result]++;
        };

        $games = Game::withoutGlobalScopes()
            ->where('season_id', $season->id)
            ->where('status', MatchStatus::Finished->value)
            ->get();

        foreach ($games as $game) {
            if ($game->is_bye) {
                $recordResult($game->home_user_id, 'wins');

                continue;
            }

            if ($game->home_goals === $game->away_goals) {
                $recordResult($game->home_user_id, 'draws');
                $recordResult($game->away_user_id, 'draws');

                continue;
            }

            $homeResult = $game->home_goals > $game->away_goals ? 'wins' : 'losses';
            $awayResult = $homeResult === 'wins' ? 'losses' : 'wins';
            $recordResult($game->home_user_id, $homeResult);
            $recordResult($game->away_user_id, $awayResult);
        }

        if ($resultsByUser === []) {
            return;
        }

        $users = User::withoutGlobalScopes()
            ->where('league_id', $season->league_id)
            ->whereIn('id', array_keys($resultsByUser))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        $transactionType = TransactionType::where('name', 'season_performance_credit')->firstOrFail();

        foreach ($resultsByUser as $userId => $results) {
            $user = $users->get($userId);

            if ($user === null) {
                continue;
            }

            $amount = bcadd(
                bcadd(
                    bcmul((string) $results['wins'], (string) $league->win_credit, 2),
                    bcmul((string) $results['draws'], (string) $league->draw_credit, 2),
                    2
                ),
                bcmul((string) $results['losses'], (string) $league->loss_credit, 2),
                2
            );

            $user->update(['balance' => bcadd((string) $user->balance, $amount, 2)]);

            FinancialTransaction::withoutGlobalScopes()->create([
                'league_id' => $season->league_id,
                'user_id' => $user->id,
                'transaction_type_id' => $transactionType->id,
                'amount' => $amount,
                'description' => "Crédito por desempenho da temporada {$season->season_number}: {$results['wins']} vitória(s), {$results['draws']} empate(s), {$results['losses']} derrota(s).",
            ]);
        }
    }

    /**
     * Debita de cada participante o total dos salários do seu elenco ao fim da temporada.
     * O saldo pode ficar negativo deliberadamente.
     */
    private function debitSquadSalaries(Season $season): void
    {
        $salariesByUser = Squad::withoutGlobalScopes()
            ->where('league_id', $season->league_id)
            ->selectRaw('user_id, SUM(salary) as total_salary')
            ->groupBy('user_id')
            ->get();

        $users = User::withoutGlobalScopes()
            ->where('league_id', $season->league_id)
            ->whereIn('id', $salariesByUser->pluck('user_id'))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($salariesByUser as $salaryByUser) {
            $user = $users->get($salaryByUser->user_id);

            if ($user !== null) {
                $user->update([
                    'balance' => bcsub((string) $user->balance, (string) $salaryByUser->total_salary, 2),
                ]);
            }
        }
    }

    private function ensureHalfIsFinished(Season $season, int $half, string $action): void
    {
        $pendingGames = Game::withoutGlobalScopes()
            ->where('season_id', $season->id)
            ->where('half', $half)
            ->where('status', '!=', MatchStatus::Finished->value)
            ->count();

        if ($pendingGames > 0) {
            throw new ApiException("Não é possível {$action}: ainda há {$pendingGames} partida(s) pendente(s) no turno.", 422);
        }
    }
}
