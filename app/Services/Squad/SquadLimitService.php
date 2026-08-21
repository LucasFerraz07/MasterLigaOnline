<?php

namespace App\Services\Squad;

use App\Enums\Category;
use App\Enums\NotificationType;
use App\Models\League;
use App\Models\LeagueCategoryPrice;
use App\Models\Squad;
use App\Services\Notification\NotificationService;
use Illuminate\Database\Eloquent\Collection;

class SquadLimitService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Libera jogadores dos elencos que excederem os limites configurados da liga.
     *
     * O limite total é aplicado antes dos limites por categoria. Em ambos os casos,
     * os atletas de maior overall saem primeiro; o id resolve empates de forma estável.
     */
    public function enforceForLeague(League $league): void
    {
        if (! $this->hasConfiguredLimit($league)) {
            return;
        }

        $squadsByUser = $this->squadsForLeague($league->id)->groupBy('user_id');

        foreach ($squadsByUser as $squads) {
            $remainingSquads = $squads;
            $squadIdsToRelease = [];

            if ($league->player_limit !== null) {
                $squadIdsToRelease = $remainingSquads
                    ->sortBy([
                        ['player_overall', 'desc'],
                        ['id', 'asc'],
                    ])
                    ->take(max(0, $remainingSquads->count() - $league->player_limit))
                    ->pluck('id')
                    ->all();

                $remainingSquads = $remainingSquads->whereNotIn('id', $squadIdsToRelease)->values();
            }

            foreach ($this->categoryLimits($league) as $category => $limit) {
                if ($limit === null) {
                    continue;
                }

                $categorySquads = $remainingSquads
                    ->where('player_category', $category)
                    ->sortBy([
                        ['player_overall', 'desc'],
                        ['id', 'asc'],
                    ]);

                $categorySquadIdsToRelease = $categorySquads
                    ->take(max(0, $categorySquads->count() - $limit))
                    ->pluck('id')
                    ->all();

                $squadIdsToRelease = [...$squadIdsToRelease, ...$categorySquadIdsToRelease];
                $remainingSquads = $remainingSquads->whereNotIn('id', $categorySquadIdsToRelease)->values();
            }

            if ($squadIdsToRelease !== []) {
                $releasedSquadIds = array_unique($squadIdsToRelease);
                $releasedSquads = $squads->whereIn('id', $releasedSquadIds);

                Squad::withoutGlobalScopes()
                    ->where('league_id', $league->id)
                    ->whereIn('id', $releasedSquadIds)
                    ->delete();

                foreach ($releasedSquads as $releasedSquad) {
                    $this->notificationService->createForUser(
                        userId: $releasedSquad->user_id,
                        type: NotificationType::PlayerReleasedByLeagueLimit,
                        title: 'Jogador liberado do elenco',
                        body: "O jogador {$releasedSquad->player_name} foi liberado do seu elenco por exceder o limite definido pela liga.",
                    );
                }
            }
        }
    }

    /** @return Collection<int, Squad> */
    private function squadsForLeague(string $leagueId): Collection
    {
        $query = Squad::withoutGlobalScopes()
            ->select([
                'squads.*',
                'players.name as player_name',
                'players.overall as player_overall',
                'league_category_prices.category as player_category',
            ])
            ->join('players', 'players.id', '=', 'squads.player_id')
            ->where('squads.league_id', $leagueId);

        // O PostgreSQL não permite bloquear o lado anulável de um LEFT JOIN.
        // A categoria é apenas consultada; somente os registros de squads
        // precisam permanecer bloqueados até que os excedentes sejam liberados.
        if ($query->getConnection()->getDriverName() === 'pgsql') {
            $query->lock('for update of squads');
        } else {
            $query->lockForUpdate();
        }

        LeagueCategoryPrice::applyBestMatchJoin($query, leagueId: $leagueId);

        return $query->get();
    }

    /** @return array<string, ?int> */
    private function categoryLimits(League $league): array
    {
        return [
            Category::Silver->value => $league->silver_limit,
            Category::Gold->value => $league->golden_limit,
            Category::Black->value => $league->black_limit,
        ];
    }

    private function hasConfiguredLimit(League $league): bool
    {
        return $league->player_limit !== null
            || $league->silver_limit !== null
            || $league->golden_limit !== null
            || $league->black_limit !== null;
    }
}
