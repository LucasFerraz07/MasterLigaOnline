<?php

namespace App\Services\FinancialTransaction;

use App\Enums\UserType;
use App\Exceptions\ApiException;
use App\Http\Resources\FinancialTransaction\FinancialTransactionCollection;
use App\Http\Resources\FinancialTransaction\FinancialTransactionResource;
use App\Models\FinancialTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FinancialTransactionService
{
    public function index(array $data): FinancialTransactionCollection
    {
        $actor = Auth::user();
        $isSystemAdmin = $actor->hasRole(UserType::SYSTEM_ADMIN->value);

        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;
        $userId = $isSystemAdmin ? ($data['user_id'] ?? null) : $actor->id;
        $leagueId = $isSystemAdmin ? ($data['league_id'] ?? null) : null;

        $query = $this->baseQuery()
            ->when($search, fn (Builder $query) => $query->where('financial_transactions.description', 'ILIKE', "%{$search}%"))
            ->when($userId, fn (Builder $query) => $query->where('financial_transactions.user_id', $userId))
            ->when($leagueId, fn (Builder $query) => $query->where('financial_transactions.league_id', $leagueId))
            ->orderByDesc('financial_transactions.created_at');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return new FinancialTransactionCollection($paginator);
    }

    public function show(array $data): FinancialTransactionResource
    {
        $actor = Auth::user();
        $isSystemAdmin = $actor->hasRole(UserType::SYSTEM_ADMIN->value);

        $transaction = $this->baseQuery()->where('financial_transactions.id', $data['id'])->firstOrFail();

        if (! $isSystemAdmin && $transaction->user_id !== $actor->id) {
            throw new ApiException('Você só pode visualizar seu próprio histórico financeiro.', 403);
        }

        return new FinancialTransactionResource($transaction);
    }

    private function baseQuery(): Builder
    {
        return FinancialTransaction::query()
            ->select('financial_transactions.*')
            ->with(['user', 'transactionType']);
    }
}
