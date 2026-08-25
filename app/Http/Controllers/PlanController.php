<?php

namespace App\Http\Controllers;

use App\Builder\ReturnApi;
use App\Http\Requests\Plan\StorePlanPriceRequest;
use App\Http\Requests\Plan\StorePlanRequest;
use App\Http\Requests\Plan\UpdatePlanRequest;
use App\Http\Resources\PlanPriceResource;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    public function __construct(private readonly PlanService $service) {}

    public function catalog(): JsonResponse
    {
        return ReturnApi::success(PlanResource::collection($this->service->catalog()), 'Planos disponíveis.');
    }

    public function index(): JsonResponse
    {
        return ReturnApi::success(PlanResource::collection($this->service->index()), 'Planos listados.');
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        return ReturnApi::success(new PlanResource($this->service->store($request->validated())), 'Plano criado.');
    }

    public function update(UpdatePlanRequest $request, Plan $plan): JsonResponse
    {
        return ReturnApi::success(new PlanResource($this->service->update($plan, $request->safe()->except('id'))), 'Plano atualizado.');
    }

    public function storePrice(StorePlanPriceRequest $request, Plan $plan): JsonResponse
    {
        return ReturnApi::success(new PlanPriceResource($this->service->addPrice($plan, $request->validated())), 'Preço criado.');
    }

    public function deactivatePrice(PlanPrice $price): JsonResponse
    {
        return ReturnApi::success(new PlanPriceResource($this->service->deactivatePrice($price)), 'Preço desativado.');
    }
}
