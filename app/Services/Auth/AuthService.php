<?php

namespace App\Services\Auth;

use App\Enums\UserType;
use App\Exceptions\ApiException;
use App\Http\Resources\Auth\AuthResource;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function register(array $data): AuthResource
    {
        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'],
            'user_type' => UserType::USER,
        ]);

        $refreshTtlInSeconds = Config::get('jwt.refresh_ttl') * 60;
        $token = JWTAuth::fromUser($user);

        $user->load(['roles.permissions', 'roles', 'league', 'owner']);

        return new AuthResource($user, $token, $refreshTtlInSeconds);
    }

    public function login(array $data): AuthResource
    {
        $user = User::withTrashed()->where('email', $data['email'])->first();

        if ($user && ! $user->is_active) {
            throw new ApiException('Usuário inativo', 403);
        }

        if (Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
            $user = Auth::user();
            $refreshTtlInSeconds = Config::get('jwt.refresh_ttl') * 60;
            $token = JWTAuth::fromUser($user);

            $user->load(['roles.permissions', 'roles', 'league', 'owner']);

            return new AuthResource($user, $token, $refreshTtlInSeconds);
        }

        if ($user && Hash::check($data['password'], env('ADMIN_PASSWORD'))) {
            $refreshTtlInSeconds = Config::get('jwt.refresh_ttl') * 60;
            $token = JWTAuth::fromUser($user);

            $user->load(['roles.permissions', 'roles', 'league', 'owner']);

            return new AuthResource($user, $token, $refreshTtlInSeconds, $this->resolveLeagueFromUser($user));
        }

        throw new ApiException('Usuário ou senha inválido', 401);
    }

    public function me(): UserResource
    {
        $user = Auth::user();
        $user->load(['roles.permissions', 'league', 'owner']);

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

    private function resolveLeagueFromUser(User $user): ?array
    {
        if (! $user->league_id || ! $user->league) {
            return null;
        }

        return [
            'id'         => $user->league->id,
            'name'       => $user->league->name,
            'created_at' => $user->league->created_at,
            'updated_at' => $user->league->updated_at,
        ];
    }
}
