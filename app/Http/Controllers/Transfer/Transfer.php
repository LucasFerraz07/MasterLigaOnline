<?php

namespace App\Http\Controllers\Transfer;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transfer\IndexTransferRequest;
use App\Http\Requests\Transfer\ShowTransferRequest;
use App\Services\Transfer\TransferService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'Transfer')]
class Transfer extends Controller
{
    public function __construct(
        private readonly TransferService $service
    ) {}

    #[Endpoint(operationId: 'indexTransfer', title: 'Index Transfer', description: '**operationId:** `indexTransfer` — Lista o histórico de transferências da liga (jogador livre, negociação e multa). Visível a qualquer participante da liga. Em **200**, `data` segue o schema **TransferCollection** (paginado). Requer permissão: transfer.view')]
    public function index(IndexTransferRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Transferências listadas com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showTransfer', title: 'Show Transfer', description: '**operationId:** `showTransfer` — Obtém uma transferência. Visível a qualquer participante da liga. Em **200**, `data` segue o schema **TransferResource**. Requer permissão: transfer.view')]
    public function show(ShowTransferRequest $request): JsonResponse
    {
        try {
            $data = $this->service->show($request->validated());
            return ReturnApi::success($data, 'Transferência encontrada com sucesso.');
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
