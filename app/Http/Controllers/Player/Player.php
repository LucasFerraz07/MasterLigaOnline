<?php

namespace App\Http\Controllers\Player;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Player\ImportPlayersRequest;
use App\Http\Requests\Player\IndexPlayerRequest;
use App\Http\Requests\Player\ShowPlayerRequest;
use App\Http\Requests\Player\UploadPlayerImageRequest;
use App\Services\Player\PlayerImportService;
use App\Services\Player\PlayerService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'Player')]
class Player extends Controller
{
    public function __construct(
        private readonly PlayerService $service,
        private readonly PlayerImportService $importService,
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

    #[Endpoint(operationId: 'importPlayer', title: 'Import Players CSV', description: '**operationId:** `importPlayer` — Importa um arquivo CSV para criar ou atualizar o catálogo global pelo nome do jogador. Envie o arquivo em `multipart/form-data`, no campo `file`; aceita CSV/TXT de até 10 MB. Em **200**, `data` informa os totais `created`, `updated` e `skipped`. Requer permissão: player.import')]
    public function import(ImportPlayersRequest $request): JsonResponse
    {
        $data = $this->importService->import($request->file('file')->path());

        /**
         * @status 200
         *
         * @body array{error: false, message: string, data: array{created: int, updated: int, skipped: int}}
         */
        return ReturnApi::success($data, 'Jogadores importados com sucesso.');
    }

    #[Endpoint(operationId: 'uploadPlayerImage', title: 'Upload Player Image', description: '**operationId:** `uploadPlayerImage` — Faz upload da imagem de um jogador. Em **200**, `data` segue o schema **PlayerResource**. Requer permissão: player.update')]
    public function uploadImage(UploadPlayerImageRequest $request): JsonResponse
    {
        try {
            $data = $this->service->uploadPlayerImage($request->validated());
            return ReturnApi::success($data, 'Imagem do jogador enviada com sucesso.');
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
