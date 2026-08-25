<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plan = Plan::create(['code' => 'development', 'name' => 'Plano de desenvolvimento', 'user_limit' => 20, 'active' => true]);
        foreach ([1 => 2990, 2 => 5490, 6 => 14990, 12 => 26990] as $months => $amount) {
            $plan->prices()->create(['code' => "development-{$months}m-v1", 'version' => 1, 'interval_months' => $months, 'amount_cents' => $amount, 'currency' => 'BRL', 'active' => true]);
        }
    }
}
