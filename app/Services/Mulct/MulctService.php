<?php

namespace App\Services\Mulct;

use App\Enums\AcquisitionType;
use App\Enums\LeaguePhase;
use App\Enums\TransferType;
use App\Enums\UserType;
use App\Exceptions\ApiException;
use App\Http\Resources\Mulct\MulctControlResource;
use App\Http\Resources\Squad\SquadResource;
use App\Models\FinancialTransaction;
use App\Models\League;
use App\Models\MulctControl;
use App\Models\Season;
use App\Models\Squad;
use App\Models\TransactionType;
use App\Models\Transfer;
use App\Models\User;
use App\Services\Squad\SquadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MulctService
{
    public function __construct(
        private readonly SquadService $squadService
    ) {}

    public function buy(array $data): SquadResource
    {
        return DB::transaction(function () use ($data): SquadResource {
            $actor = Auth::user();

            if ($actor->hasRole(UserType::SYSTEM_ADMIN->value)) {
                throw new ApiException('system_admin não participa do mercado de transferências.', 403);
            }

            $leagueId = $actor->league_id;
            $season = Season::currentFor($leagueId);

            if ($season === null || $season->phase !== LeaguePhase::FirstWindow) {
                throw new ApiException('A compra por multa só é permitida durante a Primeira Janela.', 409);
            }

            $squad = Squad::where('league_id', $leagueId)
                ->where('player_id', $data['player_id'])
                ->lockForUpdate()
                ->first();

            if ($squad === null) {
                throw new ApiException('Este jogador não pertence a nenhum elenco nesta liga.', 422);
            }

            if ($squad->user_id === $actor->id) {
                throw new ApiException('Este jogador já pertence ao seu elenco.', 422);
            }

            if ($squad->acquired_at !== null && $squad->acquired_at->greaterThanOrEqualTo($season->start_date)) {
                throw new ApiException('Este jogador foi adquirido nesta temporada e está protegido contra multa.', 422);
            }

            $actor = User::where('id', $actor->id)->lockForUpdate()->firstOrFail();
            $seller = User::where('id', $squad->user_id)->lockForUpdate()->firstOrFail();
            $league = League::findOrFail($leagueId);

            $buyerControl = $this->lockControlFor($leagueId, $actor->id, $season->id);
            $sellerControl = $this->lockControlFor($leagueId, $seller->id, $season->id);

            if ($buyerControl->buyouts_purchased >= $league->mulct_contract_limit) {
                throw new ApiException('Você já atingiu o limite de compras por multa desta temporada.', 422);
            }

            if ($sellerControl->buyouts_lost >= $league->mulct_contract_limit) {
                throw new ApiException('Este participante já atingiu o limite de perdas por multa desta temporada.', 422);
            }

            $passe = $squad->passe;
            $price = bcmul($passe, '2', 2);

            if (bccomp($price, (string) $actor->balance, 2) === 1) {
                throw new ApiException('Saldo insuficiente para pagar a multa deste jogador.', 422);
            }

            $actor->update(['balance' => bcsub((string) $actor->balance, $price, 2)]);
            $seller->update(['balance' => bcadd((string) $seller->balance, $price, 2)]);

            $squad->update([
                'user_id' => $actor->id,
                'acquisition_type' => AcquisitionType::Mulct,
                'acquired_at' => now(),
            ]);

            $buyerControl->increment('buyouts_purchased');
            $sellerControl->increment('buyouts_lost');

            Transfer::create([
                'league_id' => $leagueId,
                'player_id' => $squad->player_id,
                'seller_id' => $seller->id,
                'buyer_id' => $actor->id,
                'season_id' => $season->id,
                'type' => TransferType::Mulct,
                'amount' => $price,
            ]);

            $purchaseType = TransactionType::where('name', 'mulct_purchase')->firstOrFail();
            $saleType = TransactionType::where('name', 'mulct_sale')->firstOrFail();

            FinancialTransaction::create([
                'league_id' => $leagueId,
                'user_id' => $actor->id,
                'transaction_type_id' => $purchaseType->id,
                'amount' => $price,
                'description' => 'Compra por multa',
            ]);

            FinancialTransaction::create([
                'league_id' => $leagueId,
                'user_id' => $seller->id,
                'transaction_type_id' => $saleType->id,
                'amount' => $price,
                'description' => 'Venda por multa',
            ]);

            return $this->squadService->show(['id' => $squad->id]);
        });
    }

    public function control(): MulctControlResource
    {
        $actor = Auth::user();
        $leagueId = $actor->league_id;
        $season = Season::currentFor($leagueId);
        $league = League::findOrFail($leagueId);

        $control = $season !== null
            ? MulctControl::where('league_id', $leagueId)
                ->where('user_id', $actor->id)
                ->where('season_id', $season->id)
                ->first()
            : null;

        return new MulctControlResource((object) [
            'buyouts_purchased' => $control->buyouts_purchased ?? 0,
            'buyouts_lost' => $control->buyouts_lost ?? 0,
            'limit' => $league->mulct_contract_limit,
            'season_id' => $season?->id,
        ]);
    }

    private function lockControlFor(string $leagueId, string $userId, string $seasonId): MulctControl
    {
        $control = MulctControl::where('league_id', $leagueId)
            ->where('user_id', $userId)
            ->where('season_id', $seasonId)
            ->lockForUpdate()
            ->first();

        if ($control !== null) {
            return $control;
        }

        return MulctControl::create([
            'league_id' => $leagueId,
            'user_id' => $userId,
            'season_id' => $seasonId,
            'buyouts_purchased' => 0,
            'buyouts_lost' => 0,
        ]);
    }
}
