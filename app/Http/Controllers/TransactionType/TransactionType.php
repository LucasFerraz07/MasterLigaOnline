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

    #[Endpoint(operationId: 'indexTransactionType', title: 'Lista tipos de transação')]
    public function index(IndexTransactionTypeRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Tipos de transação listados com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeTransactionType', title: 'Cria um tipo de transação')]
    public function store(StoreTransactionTypeRequest $request): JsonResponse
    {
        try {
            $data = $this->service->store($request->validated());
            return ReturnApi::success($data, 'Tipo de transação criado com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateTransactionType', title: 'Atualiza um tipo de transação')]
    public function update(UpdateTransactionTypeRequest $request): JsonResponse
    {
        try {
            $data = $this->service->update($request->validated());
            return ReturnApi::success($data, 'Tipo de transação atualizado com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'destroyTransactionType', title: 'Remove um tipo de transação')]
    public function destroy(DeleteTransactionTypeRequest $request): JsonResponse
    {
        try {
            $this->service->destroy($request->validated());
            return ReturnApi::success(null, 'Tipo de transação deletado com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
