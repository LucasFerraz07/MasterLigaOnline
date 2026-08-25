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
use App\Services\Plan\PlanService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'Plan')]
class PlanController extends Controller
{
    public function __construct(private readonly PlanService $service) {}

    #[Endpoint(operationId: 'catalogPlan', title: 'Catalog Plan', description: '**operationId:** `catalogPlan` — Lista os planos e preços ativos disponíveis para contratação. Em **200**, `data` segue uma coleção de **PlanResource**. Não requer autenticação.')]
    public function catalog(): JsonResponse
    {
        return ReturnApi::success(PlanResource::collection($this->service->catalog()), 'Planos disponíveis.');
    }

    #[Endpoint(operationId: 'indexPlan', title: 'Index Plan', description: '**operationId:** `indexPlan` — Lista todos os planos e respectivos preços, inclusive inativos. Em **200**, `data` segue uma coleção de **PlanResource**. Requer permissão: plan.view')]
    public function index(): JsonResponse
    {
        return ReturnApi::success(PlanResource::collection($this->service->index()), 'Planos listados.');
    }

    #[Endpoint(operationId: 'storePlan', title: 'Store Plan', description: '**operationId:** `storePlan` — Cria um plano. Em **200**, `data` segue o schema **PlanResource**. Requer permissão: plan.create')]
    public function store(StorePlanRequest $request): JsonResponse
    {
        return ReturnApi::success(new PlanResource($this->service->store($request->validated())), 'Plano criado.');
    }

    #[Endpoint(operationId: 'updatePlan', title: 'Update Plan', description: '**operationId:** `updatePlan` — Atualiza um plano e retorna seus preços. Em **200**, `data` segue o schema **PlanResource**. Requer permissão: plan.update')]
    public function update(UpdatePlanRequest $request, Plan $plan): JsonResponse
    {
        return ReturnApi::success(new PlanResource($this->service->update($plan, $request->safe()->except('id'))), 'Plano atualizado.');
    }

    #[Endpoint(operationId: 'storePlanPrice', title: 'Store Plan Price', description: '**operationId:** `storePlanPrice` — Cria uma nova versão de preço para um plano; o preço ativo do mesmo intervalo é desativado. Em **200**, `data` segue o schema **PlanPriceResource**. Requer permissão: plan.update')]
    public function storePrice(StorePlanPriceRequest $request, Plan $plan): JsonResponse
    {
        return ReturnApi::success(new PlanPriceResource($this->service->addPrice($plan, $request->validated())), 'Preço criado.');
    }

    #[Endpoint(operationId: 'deactivatePlanPrice', title: 'Deactivate Plan Price', description: '**operationId:** `deactivatePlanPrice` — Desativa um preço de plano ativo. Em **200**, `data` segue o schema **PlanPriceResource**. Requer permissão: plan.update')]
    public function deactivatePrice(PlanPrice $price): JsonResponse
    {
        return ReturnApi::success(new PlanPriceResource($this->service->deactivatePrice($price)), 'Preço desativado.');
    }

    #[Endpoint(operationId: 'destroyPlan', title: 'Destroy Plan', description: '**operationId:** `destroyPlan` — Remove definitivamente um plano e todos os seus preços. A exclusão é recusada caso existam checkouts, assinaturas ou períodos de assinatura vinculados. Em **200**, `data` é `null`. Requer permissão: plan.delete')]
    public function destroy(Plan $plan): JsonResponse
    {
        $this->service->destroy($plan);

        return ReturnApi::success(null, 'Plano deletado com sucesso.');
    }
}
