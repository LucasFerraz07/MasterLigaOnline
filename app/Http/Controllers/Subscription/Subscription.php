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
use Illuminate\Http\JsonResponse;

class Subscription extends Controller
{
    public function __construct(
        private readonly SubscriptionService $service
    ) {}

    public function index(IndexSubscriptionRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Assinaturas listadas com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        try {
            $data = $this->service->store($request->validated());
            return ReturnApi::success($data, 'Assinatura criada com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    public function update(UpdateSubscriptionRequest $request): JsonResponse
    {
        try{
            $data = $this->service->update($request->validated());
            return ReturnApi::success($data, 'Assinatura atualizada com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

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
