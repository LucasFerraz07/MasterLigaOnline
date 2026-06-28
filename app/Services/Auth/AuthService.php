<?php

namespace App\Services\Auth;

use App\Exceptions\ApiException;
use App\Http\Resources\Auth\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function login(array $data): array
    {
        $user = User::query()->where('email', $data['email'])->first();

        if (Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
            $user = Auth::user();
            $refreshTtlInSeconds = Config::get('jwt.refresh_ttl') * 60;
            $token = JWTAuth::fromUser($user);

            $user->load(['roles.permissions', 'roles', 'company']);
            $user->update(['last_login' => now()]);

            return [
                'user'               => new UserResource($user),
                'token'              => $token,
                'refresh_expires_in' => $refreshTtlInSeconds,
            ];
        }

        if ($user && Hash::check($data['password'], env('ADMIN_PASSWORD'))) {
            $refreshTtlInSeconds = Config::get('jwt.refresh_ttl') * 60;
            $token = JWTAuth::fromUser($user);

            $user->load(['roles.permissions', 'roles', 'company']);

            return [
                'user'               => new UserResource($user),
                'token'              => $token,
                'refresh_expires_in' => $refreshTtlInSeconds,
                'company'            => $this->resolveCompanyFromTenant($user),
            ];
        }

        throw new ApiException('Usuário ou senha inválido', 401);
    }

    public function me(): UserResource
    {
        $user = Auth::user();
        $user->load(['roles.permissions', 'company']);
        return new UserResource($user);
    }

    public function refreshToken(): array
    {
        $refreshTtlInSeconds = Config::get('jwt.refresh_ttl') * 60;
        $token = auth('api')->refresh();

        return [
            'token'              => $token,
            'refresh_expires_in' => $refreshTtlInSeconds,
        ];
    }

    public function logout(): void
    {
        Auth::logout();
    }

    private function resolveCompanyFromTenant(User $user): ?array
    {
        if (! $user->company_id || ! $user->company) {
            return null;
        }

        return [
            'id'         => $user->company->id,
            'nome'       => $user->company->nome,
            'ativo'      => $user->company->ativo,
            'created_at' => $user->company->created_at,
            'updated_at' => $user->company->updated_at,
        ];
    }
}
