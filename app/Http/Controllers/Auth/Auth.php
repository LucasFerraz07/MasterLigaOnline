<?php

namespace App\Http\Controllers\Auth;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Http\Resources\User\UserResource;
use App\Services\Auth\AuthService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'Auth')]
class Auth extends Controller
{
    public function __construct(
        private readonly AuthService $service
    ) {}

    #[Endpoint(operationId: 'registerAuth', title: 'Register Auth', description: '**operationId:** `registerAuth` — Cadastra um novo usuário (sem liga) e retorna o token JWT. Em **200**, `data` contém `user` (**UserResource**), `token` e `refresh_expires_in`. Não requer autenticação.')]
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $data = $this->service->register($request->validated());

            /**
             * @status 200
             *
             * @body array{error: false, message: string, data: AuthResource}
             */
            return ReturnApi::success($data, 'Cadastro realizado com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'loginAuth', title: 'Login Auth', description: '**operationId:** `loginAuth` — Autentica o usuário e retorna o token JWT. Em **200**, `data` contém `user` (**UserResource**), `token`, `refresh_expires_in` e, opcionalmente, `league`. Não requer autenticação.')]
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $data = $this->service->login($request->validated());

            /**
             * @status 200
             *
             * @body array{error: false, message: string, data: AuthResource}
             */
            return ReturnApi::success($data, 'Login realizado com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'meAuth', title: 'Me Auth', description: '**operationId:** `meAuth` — Retorna os dados do usuário autenticado. Em **200**, `data` segue o schema **UserResource**. Requer autenticação.')]
    public function me(): JsonResponse
    {
        try {
            $resource = $this->service->me();

            /**
             * @status 200
             *
             * @body array{error: false, message: string, data: UserResource}
             */
            return ReturnApi::success($resource, 'Usuário autenticado.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'logoutAuth', title: 'Logout Auth', description: '**operationId:** `logoutAuth` — Realiza logout do usuário autenticado. Em **200**, `data` é `null`. Requer autenticação.')]
    public function logout(): JsonResponse
    {
        try {
            $this->service->logout();

            /**
             * @status 200
             *
             * @body array{error: false, message: string, data: null}
             */
            return ReturnApi::success(null, 'Logout realizado com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'refreshAuth', title: 'Refresh Auth', description: '**operationId:** `refreshAuth` — Renova o token JWT do usuário autenticado. Em **200**, `data` contém `token` e `refresh_expires_in`. Requer autenticação.')]
    public function refresh(): JsonResponse
    {
        try {
            $token = $this->service->refreshToken();

            /**
             * @status 200
             *
             * @body array{error: false, message: string, data: array{token: string, refresh_expires_in: int}}
             */
            return ReturnApi::success($token, 'Token renovado com sucesso.');
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
