<?php

namespace App\Http\Controllers\TransferBid;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransferBid\AcceptTransferBidRequest;
use App\Http\Requests\TransferBid\CancelTransferBidRequest;
use App\Http\Requests\TransferBid\IndexTransferBidRequest;
use App\Http\Requests\TransferBid\RejectTransferBidRequest;
use App\Http\Requests\TransferBid\ShowTransferBidRequest;
use App\Http\Requests\TransferBid\StoreTransferBidRequest;
use App\Services\TransferBid\TransferBidService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'TransferBid')]
class TransferBid extends Controller
{
    public function __construct(
        private readonly TransferBidService $service
    ) {}

    #[Endpoint(operationId: 'indexTransferBid', title: 'Index Transfer Bid', description: '**operationId:** `indexTransferBid` — Lista as propostas de negociação (enviadas e recebidas) do usuário autenticado na liga. Em **200**, `data` segue o schema **TransferBidCollection** (paginado). Requer permissão: transfer-bid.view')]
    public function index(IndexTransferBidRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Propostas de negociação listadas com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showTransferBid', title: 'Show Transfer Bid', description: '**operationId:** `showTransferBid` — Obtém uma proposta de negociação. Restrito aos participantes da proposta. Em **200**, `data` segue o schema **TransferBidResource**. Requer permissão: transfer-bid.view')]
    public function show(ShowTransferBidRequest $request): JsonResponse
    {
        try {
            $data = $this->service->show($request->validated());
            return ReturnApi::success($data, 'Proposta de negociação encontrada com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeTransferBid', title: 'Store Transfer Bid', description: '**operationId:** `storeTransferBid` — Cria uma proposta de negociação entre dois participantes da liga, com jogadores e/ou dinheiro ofertados por cada lado. Restrito a janelas de mercado aberto (Primeira Janela ou Janela Intermediária). Em **200**, `data` segue o schema **TransferBidResource**. Requer permissão: transfer-bid.create')]
    public function store(StoreTransferBidRequest $request): JsonResponse
    {
        try {
            $data = $this->service->store($request->validated());
            return ReturnApi::success($data, 'Proposta de negociação criada com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'acceptTransferBid', title: 'Accept Transfer Bid', description: '**operationId:** `acceptTransferBid` — Aceita uma proposta de negociação pendente, restrito ao destinatário. Efetiva a troca de jogadores entre os elencos e o movimento financeiro correspondente. Em **200**, `data` segue o schema **TransferBidResource**. Requer permissão: transfer-bid.update')]
    public function accept(AcceptTransferBidRequest $request): JsonResponse
    {
        try {
            $data = $this->service->accept($request->validated());
            return ReturnApi::success($data, 'Proposta de negociação aceita com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'rejectTransferBid', title: 'Reject Transfer Bid', description: '**operationId:** `rejectTransferBid` — Recusa uma proposta de negociação pendente, restrito ao destinatário. Em **200**, `data` segue o schema **TransferBidResource**. Requer permissão: transfer-bid.update')]
    public function reject(RejectTransferBidRequest $request): JsonResponse
    {
        try {
            $data = $this->service->reject($request->validated());
            return ReturnApi::success($data, 'Proposta de negociação recusada com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'cancelTransferBid', title: 'Cancel Transfer Bid', description: '**operationId:** `cancelTransferBid` — Cancela uma proposta de negociação pendente, restrito ao proponente. Em **200**, `data` segue o schema **TransferBidResource**. Requer permissão: transfer-bid.update')]
    public function cancel(CancelTransferBidRequest $request): JsonResponse
    {
        try {
            $data = $this->service->cancel($request->validated());
            return ReturnApi::success($data, 'Proposta de negociação cancelada com sucesso.');
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
