<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Plan;
use App\Models\PlanPrice;
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
            $existing = $plan->prices()->where('interval_months', $data['interval_months'])->lockForUpdate();
            $version = ((int) $existing->max('version')) + 1;
            $existing->where('active', true)->update(['active' => false]);

            return $plan->prices()->create($data + ['version' => $version, 'currency' => $data['currency'] ?? 'BRL', 'active' => true]);
        });
    }

    public function deactivatePrice(PlanPrice $price): PlanPrice
    {
        if (! $price->active) {
            throw new ApiException('Este preço já está inativo.', 409);
        }
        $price->update(['active' => false]);

        return $price->refresh();
    }
}
