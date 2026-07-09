<?php

namespace App\Http\Controllers\LeagueCategoryPrice;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\LeagueCategoryPrice\IndexLeagueCategoryPriceRequest;
use App\Http\Requests\LeagueCategoryPrice\UpdateLeagueCategoryPriceRequest;
use App\Services\LeagueCategoryPrice\LeagueCategoryPriceService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'League Category Price')]
class LeagueCategoryPrice extends Controller
{
    public function __construct(
        private readonly LeagueCategoryPriceService $service
    ) {}

    #[Endpoint(operationId: 'indexLeagueCategoryPrice', title: 'Index League Category Price')]
    public function index(IndexLeagueCategoryPriceRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());

            return ReturnApi::success($data, 'Preços por categoria listados com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateLeagueCategoryPrice', title: 'Update League Category Price')]
    public function update(UpdateLeagueCategoryPriceRequest $request): JsonResponse
    {
        try {
            $data = $this->service->update($request->validated());

            return ReturnApi::success($data, 'Preço da categoria atualizado com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
