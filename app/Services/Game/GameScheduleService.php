<?php

namespace App\Services\Game;

use App\Enums\MatchStatus;
use App\Enums\UserType;
use App\Exceptions\ApiException;
use App\Models\Game;
use App\Models\League;
use App\Models\Season;
use App\Models\User;
use Illuminate\Support\Collection;

class GameScheduleService
{
    /**
     * Gera os dois turnos da temporada com o algoritmo de tabela circular.
     *
     * O segundo turno sempre espelha o primeiro, invertendo mandante e visitante.
     * Quando há número ímpar de participantes, a posição virtual de folga gera uma
     * vitória automática em cada turno para cada participante.
     */
    public function generateForSeason(Season $season, League $league): void
    {
        $participantIds = $this->participantIds($league->id);

        if ($participantIds->count() < 2) {
            throw new ApiException('São necessários pelo menos dois participantes ativos com clube atribuído para iniciar o Primeiro Turno.', 422);
        }

        if (Game::withoutGlobalScopes()->where('season_id', $season->id)->exists()) {
            return;
        }

        $firstHalfFixtures = $this->firstHalfFixtures($participantIds->all());

        foreach ($firstHalfFixtures as $fixture) {
            $this->createGame($season, $league, $fixture, 1);
        }

        foreach ($firstHalfFixtures as $fixture) {
            $mirroredFixture = $fixture['is_bye']
                ? $fixture
                : [
                    ...$fixture,
                    'home_user_id' => $fixture['away_user_id'],
                    'away_user_id' => $fixture['home_user_id'],
                ];

            $this->createGame($season, $league, $mirroredFixture, 2);
        }
    }

    /** @return Collection<int, string> */
    private function participantIds(string $leagueId): Collection
    {
        return User::withoutGlobalScopes()
            ->select('users.id')
            ->join('club_identities', function ($join) use ($leagueId): void {
                $join->on('club_identities.user_id', '=', 'users.id')
                    ->where('club_identities.league_id', $leagueId);
            })
            ->where('users.league_id', $leagueId)
            ->whereNull('users.deleted_at')
            ->where('users.user_type', '!=', UserType::SYSTEM_ADMIN->value)
            ->orderBy('users.id')
            ->lockForUpdate()
            ->pluck('users.id');
    }

    /**
     * @param  array<int, string>  $participantIds
     * @return array<int, array{round: int, home_user_id: string, away_user_id: ?string, is_bye: bool}>
     */
    private function firstHalfFixtures(array $participantIds): array
    {
        $rotation = $participantIds;

        if (count($rotation) % 2 !== 0) {
            $rotation[] = null;
        }

        $numberOfParticipants = count($rotation);
        $fixtures = [];

        for ($round = 1; $round < $numberOfParticipants; $round++) {
            for ($position = 0; $position < $numberOfParticipants / 2; $position++) {
                $leftParticipant = $rotation[$position];
                $rightParticipant = $rotation[$numberOfParticipants - 1 - $position];

                if ($leftParticipant === null || $rightParticipant === null) {
                    $fixtures[] = [
                        'round' => $round,
                        'home_user_id' => $leftParticipant ?? $rightParticipant,
                        'away_user_id' => null,
                        'is_bye' => true,
                    ];

                    continue;
                }

                [$homeUserId, $awayUserId] = $round % 2 === 0
                    ? [$rightParticipant, $leftParticipant]
                    : [$leftParticipant, $rightParticipant];

                $fixtures[] = [
                    'round' => $round,
                    'home_user_id' => $homeUserId,
                    'away_user_id' => $awayUserId,
                    'is_bye' => false,
                ];
            }

            $fixedParticipant = array_shift($rotation);
            $lastParticipant = array_pop($rotation);
            array_unshift($rotation, $lastParticipant);
            array_unshift($rotation, $fixedParticipant);
        }

        return $fixtures;
    }

    /** @param array{round: int, home_user_id: string, away_user_id: ?string, is_bye: bool} $fixture */
    private function createGame(Season $season, League $league, array $fixture, int $half): void
    {
        Game::withoutGlobalScopes()->create([
            'league_id' => $league->id,
            'season_id' => $season->id,
            'home_user_id' => $fixture['home_user_id'],
            'away_user_id' => $fixture['away_user_id'],
            'home_goals' => 0,
            'away_goals' => 0,
            'round' => $fixture['round'],
            'half' => $half,
            'is_bye' => $fixture['is_bye'],
            'status' => $fixture['is_bye'] ? MatchStatus::Finished : MatchStatus::Pending,
        ]);
    }
}
