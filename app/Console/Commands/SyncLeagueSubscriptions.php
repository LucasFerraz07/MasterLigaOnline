<?php

namespace App\Console\Commands;

use App\Models\LeagueSubscription;
use App\Services\SubscriptionLifecycleService;
use Illuminate\Console\Command;

class SyncLeagueSubscriptions extends Command
{
    protected $signature = 'subscriptions:sync';

    protected $description = 'Ativa, expira e revoga períodos de acesso das ligas';

    public function handle(SubscriptionLifecycleService $service): int
    {
        LeagueSubscription::query()->chunkById(100, fn ($items) => $items->each(fn ($item) => $service->sync($item)));

        return self::SUCCESS;
    }
}
