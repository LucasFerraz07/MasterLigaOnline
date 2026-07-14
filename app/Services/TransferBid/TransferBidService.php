<?php

namespace App\Services\TransferBid;

use App\Enums\BidStatus;
use App\Enums\LeaguePhase;
use App\Enums\TransferItemType;
use App\Enums\TransferSide;
use App\Enums\TransferType;
use App\Enums\UserType;
use App\Exceptions\ApiException;
use App\Http\Resources\TransferBid\TransferBidCollection;
use App\Http\Resources\TransferBid\TransferBidResource;
use App\Models\FinancialTransaction;
use App\Models\LeagueCategoryPrice;
use App\Models\Season;
use App\Models\Squad;
use App\Models\TransactionType;
use App\Models\Transfer;
use App\Models\TransferBid;
use App\Models\TransferBidItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferBidService
{
    private const OPEN_WINDOW_PHASES = [LeaguePhase::FirstWindow, LeaguePhase::MidWindow];

    public function index(array $data): TransferBidCollection
    {
        $actor = Auth::user();
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $status = $data['status'] ?? null;
        $isSystemAdmin = $actor->hasRole(UserType::SYSTEM_ADMIN->value);
        $leagueId = $isSystemAdmin ? ($data['league_id'] ?? null) : null;

        $query = TransferBid::query()
            ->with(['proposer', 'receiver', 'items.player'])
            ->when(! $isSystemAdmin, function (Builder $query) use ($actor): void {
                $query->where(function (Builder $query) use ($actor): void {
                    $query->where('proposer_id', $actor->id)
                        ->orWhere('receiver_id', $actor->id);
                });
            })
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($leagueId, fn (Builder $query) => $query->where('league_id', $leagueId))
            ->orderByDesc('created_at');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $this->hydratePlayerItems($paginator->getCollection());

        return new TransferBidCollection($paginator);
    }

    public function show(array $data): TransferBidResource
    {
        $bid = TransferBid::with(['proposer', 'receiver', 'items.player'])->findOrFail($data['id']);
        $this->assertParticipant($bid, Auth::user());
        $this->hydratePlayerItems(new Collection([$bid]));

        return new TransferBidResource($bid);
    }

    public function store(array $data): TransferBidResource
    {
        return DB::transaction(function () use ($data): TransferBidResource {
            $actor = Auth::user();

            if ($actor->hasRole(UserType::SYSTEM_ADMIN->value)) {
                throw new ApiException('system_admin não participa do mercado de transferências.', 403);
            }

            $leagueId = $actor->league_id;
            $receiver = User::where('league_id', $leagueId)->find($data['receiver_id']);

            if ($receiver === null || $receiver->id === $actor->id) {
                throw new ApiException('Destinatário inválido para a proposta.', 422);
            }

            $season = $this->currentOpenSeason($leagueId);

            $offeredPlayers = $data['offered_players'] ?? [];
            $requestedPlayers = $data['requested_players'] ?? [];
            $offeredCash = isset($data['offered_cash']) ? (string) $data['offered_cash'] : null;
            $requestedCash = isset($data['requested_cash']) ? (string) $data['requested_cash'] : null;

            $this->assertOwnsPlayers($leagueId, $actor->id, $offeredPlayers, 'proponente');
            $this->assertOwnsPlayers($leagueId, $receiver->id, $requestedPlayers, 'destinatário');

            if ($offeredCash !== null && bccomp($offeredCash, (string) $actor->balance, 2) === 1) {
                throw new ApiException('Saldo insuficiente para oferecer este valor em dinheiro.', 422);
            }

            if ($requestedCash !== null && bccomp($requestedCash, (string) $receiver->balance, 2) === 1) {
                throw new ApiException('O destinatário não possui saldo suficiente para o valor solicitado.', 422);
            }

            $bid = TransferBid::create([
                'league_id' => $leagueId,
                'season_id' => $season->id,
                'proposer_id' => $actor->id,
                'receiver_id' => $receiver->id,
                'status' => BidStatus::Pending,
            ]);

            $this->createItems($bid->id, TransferSide::Proposer, $offeredPlayers, $offeredCash);
            $this->createItems($bid->id, TransferSide::Receiver, $requestedPlayers, $requestedCash);

            return $this->show(['id' => $bid->id]);
        });
    }

    public function accept(array $data): TransferBidResource
    {
        return DB::transaction(function () use ($data): TransferBidResource {
            $bid = TransferBid::with('items')->lockForUpdate()->findOrFail($data['id']);
            $actor = Auth::user();

            if ($bid->receiver_id !== $actor->id && ! $actor->hasRole(UserType::SYSTEM_ADMIN->value)) {
                throw new ApiException('Apenas o destinatário pode aceitar esta proposta.', 403);
            }

            if ($bid->status !== BidStatus::Pending) {
                throw new ApiException('Esta proposta já foi finalizada.', 409);
            }

            $season = $this->currentOpenSeason($bid->league_id);

            $proposer = User::findOrFail($bid->proposer_id);
            $receiver = User::findOrFail($bid->receiver_id);

            $proposerPlayerIds = $this->playerIdsFor($bid, TransferSide::Proposer);
            $receiverPlayerIds = $this->playerIdsFor($bid, TransferSide::Receiver);
            $offeredCash = $this->cashAmountFor($bid, TransferSide::Proposer);
            $requestedCash = $this->cashAmountFor($bid, TransferSide::Receiver);

            $this->assertOwnsPlayers($bid->league_id, $proposer->id, $proposerPlayerIds, 'proponente');
            $this->assertOwnsPlayers($bid->league_id, $receiver->id, $receiverPlayerIds, 'destinatário');

            if (bccomp($offeredCash, (string) $proposer->balance, 2) === 1) {
                throw new ApiException('O proponente não possui mais saldo suficiente para concluir esta troca.', 422);
            }

            if (bccomp($requestedCash, (string) $receiver->balance, 2) === 1) {
                throw new ApiException('O destinatário não possui mais saldo suficiente para concluir esta troca.', 422);
            }

            $this->swapPlayers($bid, $season, $proposerPlayerIds, $proposer, $receiver);
            $this->swapPlayers($bid, $season, $receiverPlayerIds, $receiver, $proposer);

            if (bccomp($offeredCash, '0', 2) === 1) {
                $this->moveCash($bid->league_id, $proposer, $receiver, $offeredCash, 'Negociação de jogadores');
            }

            if (bccomp($requestedCash, '0', 2) === 1) {
                $this->moveCash($bid->league_id, $receiver, $proposer, $requestedCash, 'Negociação de jogadores');
            }

            $bid->update(['status' => BidStatus::Accepted]);

            return $this->show(['id' => $bid->id]);
        });
    }

    public function reject(array $data): TransferBidResource
    {
        $bid = TransferBid::findOrFail($data['id']);
        $actor = Auth::user();

        if ($bid->receiver_id !== $actor->id && ! $actor->hasRole(UserType::SYSTEM_ADMIN->value)) {
            throw new ApiException('Apenas o destinatário pode recusar esta proposta.', 403);
        }

        if ($bid->status !== BidStatus::Pending) {
            throw new ApiException('Esta proposta já foi finalizada.', 409);
        }

        $bid->update(['status' => BidStatus::Rejected]);

        return $this->show(['id' => $bid->id]);
    }

    public function cancel(array $data): TransferBidResource
    {
        $bid = TransferBid::findOrFail($data['id']);
        $actor = Auth::user();

        if ($bid->proposer_id !== $actor->id && ! $actor->hasRole(UserType::SYSTEM_ADMIN->value)) {
            throw new ApiException('Apenas quem fez a proposta pode cancelá-la.', 403);
        }

        if ($bid->status !== BidStatus::Pending) {
            throw new ApiException('Esta proposta já foi finalizada.', 409);
        }

        $bid->update(['status' => BidStatus::Cancelled]);

        return $this->show(['id' => $bid->id]);
    }

    private function currentOpenSeason(string $leagueId): Season
    {
        $season = Season::currentFor($leagueId);

        if ($season === null || ! in_array($season->phase, self::OPEN_WINDOW_PHASES, true)) {
            throw new ApiException('A negociação só é permitida durante uma janela de mercado aberta.', 409);
        }

        return $season;
    }

    /**
     * @param  array<int, string>  $playerIds
     */
    private function createItems(string $bidId, TransferSide $side, array $playerIds, ?string $cash): void
    {
        if (empty($playerIds) && $cash === null) {
            throw new ApiException('A proposta precisa conter ao menos um jogador ou valor em dinheiro.', 422);
        }

        foreach ($playerIds as $playerId) {
            TransferBidItem::create([
                'bid_id' => $bidId,
                'side' => $side,
                'item_type' => TransferItemType::Player,
                'player_id' => $playerId,
            ]);
        }

        if ($cash !== null) {
            TransferBidItem::create([
                'bid_id' => $bidId,
                'side' => $side,
                'item_type' => TransferItemType::Cash,
                'amount' => $cash,
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function playerIdsFor(TransferBid $bid, TransferSide $side): array
    {
        return $bid->items
            ->where('side', $side)
            ->where('item_type', TransferItemType::Player)
            ->pluck('player_id')
            ->all();
    }

    private function cashAmountFor(TransferBid $bid, TransferSide $side): string
    {
        $item = $bid->items
            ->where('side', $side)
            ->where('item_type', TransferItemType::Cash)
            ->first();

        return (string) ($item->amount ?? '0');
    }

    /**
     * @param  array<int, string>  $playerIds
     */
    private function swapPlayers(TransferBid $bid, Season $season, array $playerIds, User $from, User $to): void
    {
        if (empty($playerIds)) {
            return;
        }

        $squads = Squad::where('league_id', $bid->league_id)
            ->whereIn('player_id', $playerIds)
            ->get()
            ->keyBy('player_id');

        foreach ($playerIds as $playerId) {
            $squad = $squads->get($playerId);
            $passe = $squad->passe;

            $squad->update(['user_id' => $to->id]);

            Transfer::create([
                'league_id' => $bid->league_id,
                'bid_id' => $bid->id,
                'player_id' => $playerId,
                'seller_id' => $from->id,
                'buyer_id' => $to->id,
                'season_id' => $season->id,
                'type' => TransferType::Negotiation,
                'amount' => $passe,
            ]);
        }
    }

    private function moveCash(string $leagueId, User $payer, User $receiver, string $amount, string $description): void
    {
        $payer->update(['balance' => bcsub((string) $payer->balance, $amount, 2)]);
        $receiver->update(['balance' => bcadd((string) $receiver->balance, $amount, 2)]);

        $purchaseType = TransactionType::where('name', 'negotiation_purchase')->firstOrFail();
        $saleType = TransactionType::where('name', 'negotiation_sale')->firstOrFail();

        FinancialTransaction::create([
            'league_id' => $leagueId,
            'user_id' => $payer->id,
            'transaction_type_id' => $purchaseType->id,
            'amount' => $amount,
            'description' => $description,
        ]);

        FinancialTransaction::create([
            'league_id' => $leagueId,
            'user_id' => $receiver->id,
            'transaction_type_id' => $saleType->id,
            'amount' => $amount,
            'description' => $description,
        ]);
    }

    /**
     * @param  array<int, string>  $playerIds
     */
    private function assertOwnsPlayers(string $leagueId, string $userId, array $playerIds, string $label): void
    {
        if (empty($playerIds)) {
            return;
        }

        $owned = Squad::where('league_id', $leagueId)
            ->where('user_id', $userId)
            ->whereIn('player_id', $playerIds)
            ->count();

        if ($owned !== count(array_unique($playerIds))) {
            throw new ApiException("Um ou mais jogadores não pertencem ao elenco do {$label}.", 422);
        }
    }

    private function assertParticipant(TransferBid $bid, User $actor): void
    {
        if ($actor->hasRole(UserType::SYSTEM_ADMIN->value)) {
            return;
        }

        if ($bid->proposer_id !== $actor->id && $bid->receiver_id !== $actor->id) {
            throw new ApiException('Você não participa desta negociação.', 403);
        }
    }

    /**
     * PlayerResource espera `category`/`salary` já resolvidos para a liga da proposta
     * (a coluna `players.category` não existe mais — é calculada por liga).
     */
    private function hydratePlayerItems(Collection $bids): void
    {
        $playerItems = $bids->flatMap(fn (TransferBid $bid) => $bid->items)
            ->filter(fn (TransferBidItem $item) => $item->item_type === TransferItemType::Player && $item->player !== null);

        if ($playerItems->isEmpty()) {
            return;
        }

        $leagueId = $bids->first()->league_id;
        $playerIds = $playerItems->pluck('player_id')->unique()->values()->all();

        $squadsQuery = Squad::query()
            ->select(['squads.*', 'league_category_prices.category as player_category'])
            ->join('players', 'players.id', '=', 'squads.player_id')
            ->where('squads.league_id', $leagueId)
            ->whereIn('squads.player_id', $playerIds);

        LeagueCategoryPrice::applyBestMatchJoin($squadsQuery, leagueIdColumn: 'squads.league_id');

        $squadsByPlayer = $squadsQuery->get()->keyBy('player_id');

        $playerItems->each(function (TransferBidItem $item) use ($squadsByPlayer): void {
            $squad = $squadsByPlayer->get($item->player_id);
            $item->player->setAttribute('category', $squad?->player_category);
            $item->player->setAttribute('salary', $squad?->salary);
        });
    }
}
