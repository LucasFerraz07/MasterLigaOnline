<?php

namespace App\Enums;

enum NotificationType: string
{
    /** Ajuste manual de saldo com crédito. */
    case ManualBalanceCredit = 'manual_balance_credit';

    /** Ajuste manual de saldo com débito. */
    case ManualBalanceDebit = 'manual_balance_debit';

    /** Início de uma nova temporada. */
    case SeasonCreated = 'season_created';

    /** Avanço ou encerramento de fase da temporada. */
    case SeasonPhaseChanged = 'season_phase_changed';

    /** Jogador liberado por exceder um limite da liga. */
    case PlayerReleasedByLeagueLimit = 'player_released_by_league_limit';

    /** Evento relacionado a uma proposta de transferência. */
    case TransferBid = 'transfer_bid';
}
