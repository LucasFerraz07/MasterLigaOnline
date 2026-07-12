<?php

namespace App\Services\Transfer;

use App\Enums\UserType;
use App\Http\Resources\Transfer\TransferCollection;
use App\Http\Resources\Transfer\TransferResource;
use App\Models\LeagueCategoryPrice;
use App\Models\Transfer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferService
{
    public function index(array $data): TransferCollection
    {
        $actor = Auth::user();
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;
        $userId = $data['user_id'] ?? null;
        $type = $data['type'] ?? null;
        $leagueId = $actor->hasRole(UserType::SYSTEM_ADMIN->value) ? ($data['league_id'] ?? null) : null;

        $query = $this->baseQuery()
            ->when($search, fn (Builder $query) => $query->where('players.name', 'ILIKE', "%{$search}%"))
            ->when($userId, function (Builder $query) use ($userId): void {
                $query->where(function (Builder $query) use ($userId): void {
                    $query->where('transfers.buyer_id', $userId)
                        ->orWhere('transfers.seller_id', $userId);
                });
            })
            ->when($type, fn (Builder $query) => $query->where('transfers.type', $type))
            ->when($leagueId, fn (Builder $query) => $query->where('transfers.league_id', $leagueId))
            ->orderByDesc('transfers.created_at');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $this->hydratePlayers($paginator->getCollection());

        return new TransferCollection($paginator);
    }

    public function show(array $data): TransferResource
    {
        $transfer = $this->baseQuery()->where('transfers.id', $data['id'])->firstOrFail();
        $this->hydratePlayers(new Collection([$transfer]));

        return new TransferResource($transfer);
    }

    private function baseQuery(): Builder
    {
        $query = Transfer::query()
            ->select([
                'transfers.*',
                'league_category_prices.category as player_category',
                DB::raw('COALESCE(squads.salary, league_category_prices.base_salary) as player_salary'),
            ])
            ->join('players', 'players.id', '=', 'transfers.player_id')
            ->leftJoin('squads', function ($join): void {
                $join->on('squads.player_id', '=', 'transfers.player_id')
                    ->on('squads.league_id', '=', 'transfers.league_id');
            })
            ->with(['player', 'seller', 'buyer']);

        LeagueCategoryPrice::applyBestMatchJoin($query, leagueIdColumn: 'transfers.league_id');

        return $query;
    }

    /**
     * PlayerResource espera `category`/`salary` já resolvidos para a liga da transferência
     * (a coluna `players.category` não existe mais — é calculada por liga).
     */
    private function hydratePlayers(Collection $transfers): void
    {
        $transfers->each(function (Transfer $transfer): void {
            $transfer->player->setAttribute('category', $transfer->player_category);
            $transfer->player->setAttribute('salary', $transfer->player_salary);
        });
    }
}
