<?php

namespace App\Http\Controllers\Auth;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
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

    #[Endpoint(operationId: 'loginAuth', title: 'Autentica um usuário')]
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $data = $this->service->login($request->validated());
            return ReturnApi::success($data, 'Login realizado com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'meAuth', title: 'Obtém o usuário autenticado')]
    public function me(): JsonResponse
    {
        try {
            $resource = $this->service->me();
            return ReturnApi::success($resource, 'Usuário autenticado.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'logoutAuth', title: 'Realiza logout do usuário')]
    public function logout(): JsonResponse
    {
        try {
            $this->service->logout();
            return ReturnApi::success(null, 'Logout realizado com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'refreshAuth', title: 'Renova o token de autenticação')]
    public function refresh(): JsonResponse
    {
        try {
            $token = $this->service->refreshToken();
            return ReturnApi::success($token, 'Token renovado com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
