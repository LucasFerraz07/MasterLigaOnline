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
use Illuminate\Http\JsonResponse;

class TransactionType extends Controller
{
    public function __construct(
        private readonly TransactionTypeService $service
    ) {}

    public function index(IndexTransactionTypeRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Tipos de transação listados com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    public function store(StoreTransactionTypeRequest $request): JsonResponse
    {
        try {
            $data = $this->service->store($request->validated());
            return ReturnApi::success($data, 'Tipo de transação criado com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    public function update(UpdateTransactionTypeRequest $request): JsonResponse
    {
        try {
            $data = $this->service->update($request->validated());
            return ReturnApi::success($data, 'Tipo de transação atualizado com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

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
