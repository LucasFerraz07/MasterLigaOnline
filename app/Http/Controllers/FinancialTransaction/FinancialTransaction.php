<?php

namespace App\Http\Controllers\FinancialTransaction;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\FinancialTransaction\IndexFinancialTransactionRequest;
use App\Http\Requests\FinancialTransaction\ShowFinancialTransactionRequest;
use App\Services\FinancialTransaction\FinancialTransactionService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'FinancialTransaction')]
class FinancialTransaction extends Controller
{
    public function __construct(
        private readonly FinancialTransactionService $service
    ) {}

    #[Endpoint(operationId: 'indexFinancialTransaction', title: 'Index Financial Transaction', description: '**operationId:** `indexFinancialTransaction` — Lista o extrato financeiro. Restrito ao próprio usuário (ninguém vê o extrato de outro participante, mesmo na mesma liga); `system_admin` pode filtrar por `user_id`/`league_id`. Em **200**, `data` segue o schema **FinancialTransactionCollection** (paginado). Requer permissão: financial-transaction.view')]
    public function index(IndexFinancialTransactionRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Extrato financeiro listado com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showFinancialTransaction', title: 'Show Financial Transaction', description: '**operationId:** `showFinancialTransaction` — Obtém um lançamento do extrato financeiro. Restrito ao próprio usuário (ou `system_admin`). Em **200**, `data` segue o schema **FinancialTransactionResource**. Requer permissão: financial-transaction.view')]
    public function show(ShowFinancialTransactionRequest $request): JsonResponse
    {
        try {
            $data = $this->service->show($request->validated());
            return ReturnApi::success($data, 'Lançamento financeiro encontrado com sucesso.');
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
