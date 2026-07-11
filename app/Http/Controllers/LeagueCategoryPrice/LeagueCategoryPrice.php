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

    #[Endpoint(operationId: 'indexLeagueCategoryPrice', title: 'Index League Category Price', description: '**operationId:** `indexLeagueCategoryPrice` — Lista preços por categoria da liga. Em **200**, `data` segue o schema **LeagueCategoryPriceCollection** (paginado). Requer permissão: league-category-price.view')]
    public function index(IndexLeagueCategoryPriceRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());

            return ReturnApi::success($data, 'Preços por categoria listados com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateLeagueCategoryPrice', title: 'Update League Category Price', description: '**operationId:** `updateLeagueCategoryPrice` — Atualiza o preço de uma categoria da liga. Em **200**, `data` segue o schema **LeagueCategoryPriceResource**. Requer permissão: league-category-price.update')]
    public function update(UpdateLeagueCategoryPriceRequest $request): JsonResponse
    {
        try {
            $data = $this->service->update($request->validated());

            return ReturnApi::success($data, 'Preço da categoria atualizado com sucesso.');
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
