<?php

namespace App\Http\Controllers\Game;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Game\IndexGameRequest;
use App\Http\Requests\Game\PublishResultGameRequest;
use App\Http\Requests\Game\ShowGameRequest;
use App\Services\Game\GameService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'Match')]
class Game extends Controller
{
    public function __construct(
        private readonly GameService $service
    ) {}

    #[Endpoint(operationId: 'indexMatch', title: 'Index Match', description: '**operationId:** `indexMatch` — Lista a tabela de jogos da liga, com filtros por temporada, turno, rodada e participante. Participantes veem os jogos da própria liga; `system_admin` deve informar `league_id`. Em **200**, `data` segue o schema **GameCollection** (paginado). Requer permissão: match.view')]
    public function index(IndexGameRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());

            return ReturnApi::success($data, 'Partidas listadas com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showMatch', title: 'Show Match', description: '**operationId:** `showMatch` — Obtém uma partida da própria liga. Em **200**, `data` segue o schema **GameResource**. Requer permissão: match.view')]
    public function show(ShowGameRequest $request): JsonResponse
    {
        try {
            $data = $this->service->show($request->validated());

            return ReturnApi::success($data, 'Partida encontrada com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'publishResultMatch', title: 'Publish Match Result', description: '**operationId:** `publishResultMatch` — Publica ou edita o placar de uma partida. Só os dois participantes ou o `league_admin` podem fazê-lo, e apenas no turno ativo correspondente ao jogo. Folgas não aceitam resultado. Em **200**, `data` segue o schema **GameResource**. Requer permissão: match.update')]
    public function publishResult(PublishResultGameRequest $request): JsonResponse
    {
        try {
            $data = $this->service->publishResult($request->validated());

            return ReturnApi::success($data, 'Resultado da partida publicado com sucesso.');
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
