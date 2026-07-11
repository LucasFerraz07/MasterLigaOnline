<?php

namespace App\Http\Controllers\TransactionType;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionType\DeleteTransactionTypeRequest;
use App\Http\Requests\TransactionType\IndexTransactionTypeRequest;
use App\Http\Requests\TransactionType\StoreTransactionTypeRequest;
use App\Http\Requests\TransactionType\UpdateTransactionTypeRequest;
use App\Services\TransactionType\TransactionTypeService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'TransactionType')]
class TransactionType extends Controller
{
    public function __construct(
        private readonly TransactionTypeService $service
    ) {}

    #[Endpoint(operationId: 'indexTransactionType', title: 'Index Transaction Type', description: '**operationId:** `indexTransactionType` — Lista tipos de transação. Em **200**, `data` segue o schema **TransactionTypeCollection** (paginado). Requer permissão: transaction-type.view')]
    public function index(IndexTransactionTypeRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Tipos de transação listados com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeTransactionType', title: 'Store Transaction Type', description: '**operationId:** `storeTransactionType` — Cria um tipo de transação. Em **200**, `data` segue o schema **TransactionTypeResource**. Requer permissão: transaction-type.create')]
    public function store(StoreTransactionTypeRequest $request): JsonResponse
    {
        try {
            $data = $this->service->store($request->validated());
            return ReturnApi::success($data, 'Tipo de transação criado com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateTransactionType', title: 'Update Transaction Type', description: '**operationId:** `updateTransactionType` — Atualiza um tipo de transação. Em **200**, `data` segue o schema **TransactionTypeResource**. Requer permissão: transaction-type.update')]
    public function update(UpdateTransactionTypeRequest $request): JsonResponse
    {
        try {
            $data = $this->service->update($request->validated());
            return ReturnApi::success($data, 'Tipo de transação atualizado com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'destroyTransactionType', title: 'Destroy Transaction Type', description: '**operationId:** `destroyTransactionType` — Remove um tipo de transação. Em **200**, `data` é `null`. Requer permissão: transaction-type.delete')]
    public function destroy(DeleteTransactionTypeRequest $request): JsonResponse
    {
        try {
            $this->service->destroy($request->validated());
            return ReturnApi::success(null, 'Tipo de transação deletado com sucesso.');
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
