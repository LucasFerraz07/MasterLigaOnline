<?php

namespace App\Http\Controllers\League;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\League\DeleteLeagueRequest;
use App\Http\Requests\League\ForceDeleteLeagueRequest;
use App\Http\Requests\League\IndexLeagueRequest;
use App\Http\Requests\League\RestoreLeagueRequest;
use App\Http\Requests\League\ShowLeagueRequest;
use App\Http\Requests\League\UpdateLeagueRequest;
use App\Services\League\LeagueService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'League')]
class League extends Controller
{
    public function __construct(
        private readonly LeagueService $service
    ) {}

    #[Endpoint(operationId: 'indexLeague', title: 'Index League', description: '**operationId:** `indexLeague` — Lista as ligas. Em **200**, `data` segue o schema **LeagueCollection** (paginado). Requer permissão: league.view')]
    public function index(IndexLeagueRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());

            return ReturnApi::success($data, 'Ligas listadas com sucesso!');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showLeague', title: 'Show League', description: '**operationId:** `showLeague` — Obtém uma liga. Em **200**, `data` segue o schema **LeagueResource**. Requer permissão: league.view')]
    public function show(ShowLeagueRequest $request): JsonResponse
    {
        try {
            $data = $this->service->show($request->validated());

            return ReturnApi::success($data, 'Liga encontrada com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateLeague', title: 'Update League', description: '**operationId:** `updateLeague` — Atualiza uma liga. Em **200**, `data` segue o schema **LeagueResource**. Requer permissão: league.update')]
    public function update(UpdateLeagueRequest $request): JsonResponse
    {
        try {
            $data = $this->service->update($request->validated());

            return ReturnApi::success($data, 'Liga atualizada com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'deleteLeague', title: 'Delete League', description: '**operationId:** `deleteLeague` — Remove (soft delete) uma liga. Em **200**, `data` é `null`. Requer permissão: league.delete')]
    public function delete(DeleteLeagueRequest $request): JsonResponse
    {
        try {
            $this->service->delete($request->validated());

            return ReturnApi::success(null, 'Liga deletada com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'restoreLeague', title: 'Restore League', description: '**operationId:** `restoreLeague` — Restaura uma liga deletada (soft delete). Em **200**, `data` é `null`. Requer permissão: league.restore')]
    public function restore(RestoreLeagueRequest $request): JsonResponse
    {
        try {
            $this->service->restore($request->validated());

            return ReturnApi::success(null, 'Liga restaurada com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'destroyLeague', title: 'Destroy League', description: '**operationId:** `destroyLeague` — Apaga definitivamente uma liga e todos os dados vinculados a ela. Em **200**, `data` é `null`. Requer permissão: league.force-delete')]
    public function destroy(ForceDeleteLeagueRequest $request): JsonResponse
    {
        try {
            $this->service->destroy($request->validated());

            return ReturnApi::success(null, 'Liga apagada definitivamente com sucesso.');
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
