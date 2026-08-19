<?php

namespace App\Services\Squad;

use App\Enums\AcquisitionType;
use App\Enums\LeaguePhase;
use App\Enums\TransferType;
use App\Enums\UserType;
use App\Exceptions\ApiException;
use App\Http\Resources\Squad\SquadCollection;
use App\Http\Resources\Squad\SquadResource;
use App\Models\FinancialTransaction;
use App\Models\LeagueCategoryPrice;
use App\Models\Player;
use App\Models\Season;
use App\Models\Squad;
use App\Models\TransactionType;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SquadService
{
    private const RELEASE_WINDOW_PHASES = [LeaguePhase::FirstWindow, LeaguePhase::MidWindow];

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
        $this->hydratePlayers($paginator->getCollection());

        return new SquadCollection($paginator);
    }

    public function show(array $data): SquadResource
    {
        $squad = $this->baseQuery()->where('squads.id', $data['id'])->firstOrFail();
        $this->hydratePlayers(new Collection([$squad]));

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

    public function buyFreeAgent(array $data): SquadResource
    {
        return DB::transaction(function () use ($data): SquadResource {
            $actor = Auth::user();

            if ($actor->hasRole(UserType::SYSTEM_ADMIN->value)) {
                throw new ApiException('system_admin não participa do mercado de transferências.', 403);
            }

            $userId = $actor->id;
            $leagueId = $actor->league_id;

            $season = Season::currentFor($leagueId);

            if ($season === null || ! in_array($season->phase, [LeaguePhase::FirstWindow, LeaguePhase::MidWindow], true)) {
                throw new ApiException('A compra de jogador livre só é permitida durante uma janela de mercado aberta.', 409);
            }

            $alreadyOwned = Squad::query()
                ->where('league_id', $leagueId)
                ->where('player_id', $data['player_id'])
                ->exists();

            if ($alreadyOwned) {
                throw new ApiException('Este jogador já pertence a um elenco nesta liga.', 422);
            }

            $player = Player::findOrFail($data['player_id']);

            $leagueCategoryPrice = LeagueCategoryPrice::query()
                ->where('league_id', $leagueId)
                ->where('min_overall', '<=', $player->overall)
                ->orderByDesc('min_overall')
                ->first();

            if ($leagueCategoryPrice === null) {
                throw new ApiException('Não há faixa de preço configurada para a categoria deste jogador nesta liga.', 422);
            }

            $salary = (string) $leagueCategoryPrice->base_salary;
            $passe = bcmul($salary, '10', 2);

            $actor = User::where('id', $actor->id)->lockForUpdate()->firstOrFail();

            if (bccomp($passe, (string) $actor->balance, 2) === 1) {
                throw new ApiException('Saldo insuficiente para comprar este jogador.', 422);
            }

            $squad = Squad::create([
                'league_id' => $leagueId,
                'user_id' => $userId,
                'player_id' => $player->id,
                'acquisition_type' => AcquisitionType::Initial,
                'salary' => $salary,
                'acquired_at' => now(),
            ]);

            $actor->update(['balance' => bcsub((string) $actor->balance, $passe, 2)]);

            Transfer::create([
                'league_id' => $leagueId,
                'player_id' => $player->id,
                'seller_id' => null,
                'buyer_id' => $userId,
                'season_id' => $season->id,
                'type' => TransferType::Free,
                'amount' => $passe,
            ]);

            $transactionType = TransactionType::where('name', 'free_purchase')->firstOrFail();

            FinancialTransaction::create([
                'league_id' => $leagueId,
                'user_id' => $userId,
                'transaction_type_id' => $transactionType->id,
                'amount' => $passe,
                'description' => "Compra de jogador livre: {$player->name}",
            ]);

            return $this->show(['id' => $squad->id]);
        });
    }

    /**
     * Dispensa um jogador e o devolve ao mercado livre.
     *
     * A taxa de dispensa é metade do passe vigente do jogador no elenco.
     */
    public function releasePlayer(array $data): void
    {
        DB::transaction(function () use ($data): void {
            $actor = Auth::user();

            if ($actor->hasRole(UserType::SYSTEM_ADMIN->value)) {
                throw new ApiException('system_admin não participa do mercado de transferências.', 403);
            }

            $leagueId = $actor->league_id;
            $season = Season::currentFor($leagueId);

            if ($season === null || ! in_array($season->phase, self::RELEASE_WINDOW_PHASES, true)) {
                throw new ApiException('A dispensa de jogador só é permitida durante a Primeira ou a Última Janela.', 409);
            }

            $squad = Squad::query()
                ->where('id', $data['id'])
                ->where('league_id', $leagueId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($squad->user_id !== $actor->id) {
                throw new ApiException('Apenas o dono do elenco pode dispensar este jogador.', 403);
            }

            $actor = User::where('id', $actor->id)->lockForUpdate()->firstOrFail();
            $fee = bcdiv($squad->passe, '2', 2);

            if (bccomp($fee, (string) $actor->balance, 2) === 1) {
                throw new ApiException('Saldo insuficiente para pagar a dispensa deste jogador.', 422);
            }

            $transactionType = TransactionType::where('name', 'player_release')->firstOrFail();

            $actor->update(['balance' => bcsub((string) $actor->balance, $fee, 2)]);

            FinancialTransaction::create([
                'league_id' => $leagueId,
                'user_id' => $actor->id,
                'transaction_type_id' => $transactionType->id,
                'amount' => $fee,
                'description' => "Dispensa de jogador: {$squad->player->name}",
            ]);

            $squad->delete();
        });
    }

    private function baseQuery(): Builder
    {
        $query = Squad::query()
            ->select([
                'squads.*',
                'league_category_prices.category as player_category',
            ])
            ->join('players', 'players.id', '=', 'squads.player_id')
            ->with(['user', 'player']);

        LeagueCategoryPrice::applyBestMatchJoin($query, leagueIdColumn: 'squads.league_id');

        return $query;
    }

    /**
     * PlayerResource espera `category`/`salary` já resolvidos para a liga do squad
     * (a coluna `players.category` não existe mais — é calculada por liga). Como aqui
     * o jogador já pertence a este squad, o salário correto é sempre o do próprio squad.
     */
    private function hydratePlayers(Collection $squads): void
    {
        $squads->each(function (Squad $squad): void {
            $squad->player->setAttribute('category', $squad->player_category);
            $squad->player->setAttribute('salary', $squad->salary);
        });
    }
}
