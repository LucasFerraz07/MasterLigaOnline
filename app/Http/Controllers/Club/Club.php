<?php

namespace App\Http\Controllers\Club;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\DeleteClubRequest;
use App\Http\Requests\Club\IndexClubRequest;
use App\Http\Requests\Club\StoreClubRequest;
use App\Http\Requests\Club\UpdateClubRequest;
use App\Services\Club\ClubService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'Club')]
class Club extends Controller
{
    public function __construct(
        private readonly ClubService $service
    ) {}

    #[Endpoint(operationId: 'indexClub', title: 'Index Club', description: '**operationId:** `indexClub` — Lista os clubes. Em **200**, `data` segue o schema **ClubCollection** (paginado). Requer permissão: club.view')]
    public function index(IndexClubRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Clubes listados com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeClub', title: 'Store Club', description: '**operationId:** `storeClub` — Cria um clube. Em **200**, `data` segue o schema **ClubResource**. Requer permissão: club.create')]
    public function store(StoreClubRequest $request): JsonResponse
    {
        try {
            $data = $this->service->store($request->validated());
            return ReturnApi::success($data, 'Clube criado com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateClub', title: 'Update Club', description: '**operationId:** `updateClub` — Atualiza um clube. Em **200**, `data` segue o schema **ClubResource**. Requer permissão: club.update')]
    public function update(UpdateClubRequest $request): JsonResponse
    {
        try {
            $data = $this->service->update($request->validated());
            return ReturnApi::success($data, 'Clube atualizado com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'destroyClub', title: 'Destroy Club', description: '**operationId:** `destroyClub` — Remove um clube. Em **200**, `data` é `null`. Requer permissão: club.delete')]
    public function destroy(DeleteClubRequest $request): JsonResponse
    {
        try {
            $this->service->destroy($request->validated());
            return ReturnApi::success(null, 'Clube deletado com sucesso.');
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
