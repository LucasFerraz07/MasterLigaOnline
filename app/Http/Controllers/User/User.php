<?php

namespace App\Http\Controllers\User;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\DeleteUserRequest;
use App\Http\Requests\User\IndexUserRequest;
use App\Http\Requests\User\ShowUserRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Services\User\UserService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'User')]
class User extends Controller
{
    public function __construct(
        private readonly UserService $service
    ) {}

    #[Endpoint(operationId: 'indexUser', title: 'Lista usuários')]
    public function index(IndexUserRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Usuários listados com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showUser', title: 'Obtém um usuário')]
    public function show(ShowUserRequest $request): JsonResponse
    {
        try {
            $data = $this->service->show($request->validated());
            return ReturnApi::success($data, 'Usuário encontrado com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeUser', title: 'Cria um usuário')]
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $data = $this->service->store($request->validated());
            return ReturnApi::success($data, 'Usuário criado com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateUser', title: 'Atualiza um usuário')]
    public function update(UpdateUserRequest $request): JsonResponse
    {
        try {
            $data = $this->service->update($request->validated());
            return ReturnApi::success($data, 'Usuário atualizado com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'destroyUser', title: 'Remove um usuário')]
    public function destroy(DeleteUserRequest $request): JsonResponse
    {
        try {
            $this->service->destroy($request->validated());
            return ReturnApi::success(null, 'Usuário deletado com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
