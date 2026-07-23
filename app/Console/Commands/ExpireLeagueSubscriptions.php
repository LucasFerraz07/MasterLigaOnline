<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionPeriodStatus;
use App\Models\League;
use App\Models\SubscriptionPeriod;
use Illuminate\Console\Command;

class ExpireLeagueSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Desativa (sem excluir) ligas cuja assinatura venceu e marca os períodos ativos como expirados';

    public function handle(): int
    {
        $leagues = League::query()
            ->whereNull('deactivated_at')
            ->whereDate('subscription_end', '<', now())
            ->get();

        foreach ($leagues as $league) {
            $league->update(['deactivated_at' => now()]);

            SubscriptionPeriod::query()
                ->where('league_id', $league->id)
                ->where('status', SubscriptionPeriodStatus::ACTIVE)
                ->update(['status' => SubscriptionPeriodStatus::EXPIRED]);
        }

        $this->info("{$leagues->count()} liga(s) desativada(s) por assinatura vencida.");

        return self::SUCCESS;
    }
}
