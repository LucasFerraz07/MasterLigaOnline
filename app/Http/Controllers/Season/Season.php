<?php

namespace App\Http\Controllers\Season;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Season\AdvancePhaseSeasonRequest;
use App\Http\Requests\Season\IndexSeasonRequest;
use App\Http\Requests\Season\ShowSeasonRequest;
use App\Http\Requests\Season\StoreSeasonRequest;
use App\Services\Season\SeasonService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'Season')]
class Season extends Controller
{
    public function __construct(
        private readonly SeasonService $service
    ) {}

    #[Endpoint(operationId: 'indexSeason', title: 'Index Season', description: '**operationId:** `indexSeason` — Lista temporadas. Em **200**, `data` segue o schema **SeasonCollection** (paginado). Requer permissão: season.view')]
    public function index(IndexSeasonRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());

            return ReturnApi::success($data, 'Temporadas listadas com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showSeason', title: 'Show Season', description: '**operationId:** `showSeason` — Obtém uma temporada. Em **200**, `data` segue o schema **SeasonResource**. Requer permissão: season.view')]
    public function show(ShowSeasonRequest $request): JsonResponse
    {
        try {
            $data = $this->service->show($request->validated());

            return ReturnApi::success($data, 'Temporada encontrada com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeSeason', title: 'Store Season', description: '**operationId:** `storeSeason` — Cria uma temporada. Em **200**, `data` segue o schema **SeasonResource**. Requer permissão: season.create')]
    public function store(StoreSeasonRequest $request): JsonResponse
    {
        try {
            $data = $this->service->store($request->validated());

            return ReturnApi::success($data, 'Temporada criada com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'advancePhaseSeason', title: 'Advance Season Phase', description: '**operationId:** `advancePhaseSeason` — Avança a fase da temporada. Ao sair da Primeira Janela, aplica os limites de elenco da liga e libera os jogadores excedentes de maior overall. Em **200**, `data` segue o schema **SeasonResource**. Requer permissão: season.update')]
    public function advancePhase(AdvancePhaseSeasonRequest $request): JsonResponse
    {
        try {
            $data = $this->service->advancePhase($request->validated());

            return ReturnApi::success($data, 'Fase da temporada avançada com sucesso.');
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
