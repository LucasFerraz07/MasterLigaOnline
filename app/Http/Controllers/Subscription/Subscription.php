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

    #[Endpoint(operationId: 'indexSubscription', title: 'Lista assinaturas')]
    public function index(IndexSubscriptionRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Assinaturas listadas com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeSubscription', title: 'Cria uma assinatura')]
    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        try {
            $data = $this->service->store($request->validated());
            return ReturnApi::success($data, 'Assinatura criada com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateSubscription', title: 'Atualiza uma assinatura')]
    public function update(UpdateSubscriptionRequest $request): JsonResponse
    {
        try{
            $data = $this->service->update($request->validated());
            return ReturnApi::success($data, 'Assinatura atualizada com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'destroySubscription', title: 'Remove uma assinatura')]
    public function destroy(DeleteSubscriptionRequest $request): JsonResponse
    {
        try {
            $this->service->destroy($request->validated());
            return ReturnApi::success(null, 'Assinatura removida com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
