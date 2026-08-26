<?php

namespace App\Services\Plan;

use App\Exceptions\ApiException;
use App\Models\Checkout;
use App\Models\LeagueSubscription;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\SubscriptionPeriod;
use Illuminate\Support\Facades\DB;

class PlanService
{
    public function catalog()
    {
        return Plan::query()->where('active', true)->with(['prices' => fn ($q) => $q->where('active', true)->orderBy('interval_months')])->orderBy('name')->get();
    }

    public function index()
    {
        return Plan::with(['prices' => fn ($q) => $q->orderBy('interval_months')->orderByDesc('version')])->orderBy('name')->get();
    }

    public function store(array $data): Plan
    {
        return Plan::create($data);
    }

    public function update(Plan $plan, array $data): Plan
    {
        $plan->update($data);

        return $plan->refresh()->load('prices');
    }

    public function addPrice(Plan $plan, array $data): PlanPrice
    {
        return DB::transaction(function () use ($plan, $data) {
            $existing = $plan->prices()->where('interval_months', $data['interval_months']);
            $version = ((int) $existing->max('version')) + 1;
            $existing->where('active', true)->update(['active' => false]);

            return $plan->prices()->create($data + ['version' => $version, 'currency' => $data['currency'] ?? 'BRL', 'active' => true]);
        });
    }

    public function changePriceStatus(PlanPrice $price): PlanPrice
    {
        $price->update(['active' => ! $price->active]);

        return $price->refresh();
    }

    public function destroy(Plan $plan): void
    {
        DB::transaction(function () use ($plan): void {
            $plan = Plan::query()->lockForUpdate()->findOrFail($plan->id);
            $priceIds = $plan->prices()->lockForUpdate()->pluck('id');

            $hasLinkedRecords = Checkout::query()->whereIn('plan_price_id', $priceIds)->exists()
                || LeagueSubscription::query()
                    ->where('current_plan_id', $plan->id)
                    ->orWhereIn('current_plan_price_id', $priceIds)
                    ->exists()
                || SubscriptionPeriod::query()
                    ->where('plan_id', $plan->id)
                    ->orWhereIn('plan_price_id', $priceIds)
                    ->exists();

            if ($hasLinkedRecords) {
                throw new ApiException('Não é possível excluir um plano que possui checkouts, assinaturas ou períodos de assinatura vinculados.', 409);
            }

            $plan->prices()->delete();
            $plan->delete();
        });
    }
}
