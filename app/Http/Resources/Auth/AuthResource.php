<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    public function __construct(
        private readonly mixed $user,
        private readonly string $token,
        private readonly int $refreshExpiresIn,
        private readonly ?array $league = null,
    ) {
        parent::__construct($user);
    }

    public function toArray(Request $request): array
    {
        return array_filter([
            'user'               => new UserResource($this->user),
            'token'              => (string) $this->token,
            'refresh_expires_in' => $this->refreshExpiresIn,
            'league'             => $this->league,
        ], fn ($value) => ! is_null($value));
    }
}
