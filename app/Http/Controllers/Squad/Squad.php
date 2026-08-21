<?php

namespace App\Http\Controllers\Squad;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Squad\AdjustSalarySquadRequest;
use App\Http\Requests\Squad\BuyFreeAgentSquadRequest;
use App\Http\Requests\Squad\IndexSquadRequest;
use App\Http\Requests\Squad\ReleasePlayerSquadRequest;
use App\Http\Requests\Squad\ShowSquadRequest;
use App\Services\Squad\SquadService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'Squad')]
class Squad extends Controller
{
    public function __construct(
        private readonly SquadService $service
    ) {}

    #[Endpoint(operationId: 'indexSquad', title: 'Index Squad', description: '**operationId:** `indexSquad` — Lista os elencos da liga. Em **200**, `data` segue o schema **SquadCollection** (paginado). Requer permissão: squad.view')]
    public function index(IndexSquadRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Elencos listados com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showSquad', title: 'Show Squad', description: '**operationId:** `showSquad` — Obtém um elenco. Em **200**, `data` segue o schema **SquadResource**. Requer permissão: squad.view')]
    public function show(ShowSquadRequest $request): JsonResponse
    {
        try {
            $data = $this->service->show($request->validated());
            return ReturnApi::success($data, 'Elenco encontrado com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'adjustSquadSalary', title: 'Adjust Squad Salary', description: '**operationId:** `adjustSquadSalary` — Reajusta o salário de um jogador no elenco, restrito ao dono do elenco durante a Janela Inicial. Em **200**, `data` segue o schema **SquadResource**. Requer permissão: squad.update')]
    public function adjustSalary(AdjustSalarySquadRequest $request): JsonResponse
    {
        try {
            $data = $this->service->adjustSalary($request->validated());
            return ReturnApi::success($data, 'Salário reajustado com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'buyFreeAgentSquad', title: 'Buy Free Agent', description: '**operationId:** `buyFreeAgentSquad` — Compra um jogador livre para o próprio elenco, debitando o passe (10× o salário-base da categoria) do saldo do comprador. Restrito a janelas de mercado aberto (Primeira Janela ou Janela Intermediária). Em **200**, `data` segue o schema **SquadResource**. Requer permissão: squad.create')]
    public function buyFreeAgent(BuyFreeAgentSquadRequest $request): JsonResponse
    {
        try {
            $data = $this->service->buyFreeAgent($request->validated());
            return ReturnApi::success($data, 'Jogador contratado com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'releaseSquadPlayer', title: 'Release Squad Player', description: '**operationId:** `releaseSquadPlayer` — Dispensa um jogador do próprio elenco e o devolve ao mercado livre, cobrando metade do passe vigente. Restrito à Primeira ou Janela Intermediária. Em **200**, `data` é `null`. Requer permissão: squad.update')]
    public function releasePlayer(ReleasePlayerSquadRequest $request): JsonResponse
    {
        try {
            $this->service->releasePlayer($request->validated());
            return ReturnApi::success(null, 'Jogador dispensado com sucesso.');
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
