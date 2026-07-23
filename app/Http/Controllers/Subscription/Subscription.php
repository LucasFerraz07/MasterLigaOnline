<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Requests\Subscription\DeleteSubscriptionRequest;
use App\Http\Requests\Subscription\IndexSubscriptionRequest;
use App\Http\Requests\Subscription\StoreSubscriptionRequest;
use App\Http\Requests\Subscription\UpdateSubscriptionRequest;
use App\Services\Subscription\SubscriptionService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'Subscription')]
class Subscription extends Controller
{
    public function __construct(
        private readonly SubscriptionService $service
    ) {}

    #[Endpoint(operationId: 'catalogSubscription', title: 'Catalog Subscription', description: '**operationId:** `catalogSubscription` — Lista os planos disponíveis para contratação. Em **200**, `data` segue o schema **SubscriptionCollection** (paginado). Requer apenas autenticação (sem permissão específica) — usado pela tela de escolha de plano antes do pagamento.')]
    public function catalog(IndexSubscriptionRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Planos listados com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'indexSubscription', title: 'Index Subscription', description: '**operationId:** `indexSubscription` — Lista assinaturas. Em **200**, `data` segue o schema **SubscriptionCollection** (paginado). Requer permissão: subscription.view')]
    public function index(IndexSubscriptionRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Assinaturas listadas com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeSubscription', title: 'Store Subscription', description: '**operationId:** `storeSubscription` — Cria uma assinatura. Em **200**, `data` segue o schema **SubscriptionResource**. Requer permissão: subscription.create')]
    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        try {
            $data = $this->service->store($request->validated());
            return ReturnApi::success($data, 'Assinatura criada com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateSubscription', title: 'Update Subscription', description: '**operationId:** `updateSubscription` — Atualiza uma assinatura. Em **200**, `data` segue o schema **SubscriptionResource**. Requer permissão: subscription.update')]
    public function update(UpdateSubscriptionRequest $request): JsonResponse
    {
        try{
            $data = $this->service->update($request->validated());
            return ReturnApi::success($data, 'Assinatura atualizada com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'destroySubscription', title: 'Destroy Subscription', description: '**operationId:** `destroySubscription` — Remove uma assinatura. Em **200**, `data` é `null`. Requer permissão: subscription.delete')]
    public function destroy(DeleteSubscriptionRequest $request): JsonResponse
    {
        try {
            $this->service->destroy($request->validated());
            return ReturnApi::success(null, 'Assinatura removida com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
