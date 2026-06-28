<?php

namespace App\Http\Controllers\Auth;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class Auth extends Controller
{
    public function __construct(
        private readonly AuthService $service
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $data = $this->service->login($request->validated());
            return ReturnApi::success($data, 'Login realizado com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    public function me(): JsonResponse
    {
        try {
            $resource = $this->service->me();
            return ReturnApi::success($resource, 'Usuário autenticado.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $this->service->logout();
            return ReturnApi::success(null, 'Logout realizado com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

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
