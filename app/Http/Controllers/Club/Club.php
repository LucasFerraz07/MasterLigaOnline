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

    #[Endpoint(operationId: 'indexClub', title: 'Lista clubes')]
    public function index(IndexClubRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Clubes listados com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeClub', title: 'Cria um clube')]
    public function store(StoreClubRequest $request): JsonResponse
    {
        try {
            $data = $this->service->store($request->validated());
            return ReturnApi::success($data, 'Clube criado com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateClub', title: 'Atualiza um clube')]
    public function update(UpdateClubRequest $request): JsonResponse
    {
        try {
            $data = $this->service->update($request->validated());
            return ReturnApi::success($data, 'Clube atualizado com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'destroyClub', title: 'Remove um clube')]
    public function destroy(DeleteClubRequest $request): JsonResponse
    {
        try {
            $this->service->destroy($request->validated());
            return ReturnApi::success(null, 'Clube deletado com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
