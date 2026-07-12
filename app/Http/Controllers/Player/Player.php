<?php

namespace App\Http\Controllers\Player;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Player\IndexPlayerRequest;
use App\Http\Requests\Player\ShowPlayerRequest;
use App\Services\Player\PlayerService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'Player')]
class Player extends Controller
{
    public function __construct(
        private readonly PlayerService $service
    ) {}

    #[Endpoint(operationId: 'indexPlayer', title: 'Index Player', description: '**operationId:** `indexPlayer` — Lista os jogadores do catálogo global. Em **200**, `data` segue o schema **PlayerCollection** (paginado). Requer permissão: player.view')]
    public function index(IndexPlayerRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Jogadores listados com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showPlayer', title: 'Show Player', description: '**operationId:** `showPlayer` — Obtém um jogador. Em **200**, `data` segue o schema **PlayerResource**. Requer permissão: player.view')]
    public function show(ShowPlayerRequest $request): JsonResponse
    {
        try {
            $data = $this->service->show($request->validated());
            return ReturnApi::success($data, 'Jogador encontrado com sucesso.');
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
