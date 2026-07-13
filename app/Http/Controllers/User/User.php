<?php

namespace App\Http\Controllers\User;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\AdjustBalanceUserRequest;
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

    #[Endpoint(operationId: 'indexUser', title: 'Index User', description: '**operationId:** `indexUser` — Lista usuários. Em **200**, `data` segue o schema **UserCollection** (paginado). Requer permissão: user.view')]
    public function index(IndexUserRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Usuários listados com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showUser', title: 'Show User', description: '**operationId:** `showUser` — Obtém um usuário. Em **200**, `data` segue o schema **UserResource**. Requer permissão: user.view')]
    public function show(ShowUserRequest $request): JsonResponse
    {
        try {
            $data = $this->service->show($request->validated());
            return ReturnApi::success($data, 'Usuário encontrado com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeUser', title: 'Store User', description: '**operationId:** `storeUser` — Cria um usuário. Em **200**, `data` segue o schema **UserResource**. Requer permissão: user.create')]
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $data = $this->service->store($request->validated());
            return ReturnApi::success($data, 'Usuário criado com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateUser', title: 'Update User', description: '**operationId:** `updateUser` — Atualiza um usuário. Em **200**, `data` segue o schema **UserResource**. Requer permissão: user.update')]
    public function update(UpdateUserRequest $request): JsonResponse
    {
        try {
            $data = $this->service->update($request->validated());
            return ReturnApi::success($data, 'Usuário atualizado com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'destroyUser', title: 'Destroy User', description: '**operationId:** `destroyUser` — Remove um usuário. Em **200**, `data` é `null`. Requer permissão: user.delete')]
    public function destroy(DeleteUserRequest $request): JsonResponse
    {
        try {
            $this->service->destroy($request->validated());
            return ReturnApi::success(null, 'Usuário deletado com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'adjustBalanceUser', title: 'Adjust User Balance', description: '**operationId:** `adjustBalanceUser` — Credita ou debita um valor no saldo de um usuário da própria liga (ex.: crédito de ingresso na liga). Restrito ao dono da liga (`league_admin`). Em **200**, `data` segue o schema **UserResource**. Requer permissão: user.balance')]
    public function adjustBalance(AdjustBalanceUserRequest $request): JsonResponse
    {
        try {
            $data = $this->service->adjustBalance($request->validated());
            return ReturnApi::success($data, 'Saldo ajustado com sucesso.');
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
