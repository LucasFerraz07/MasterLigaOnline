<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    public function __construct(
        private readonly mixed $user,
        private readonly string $token,
    ) {
        parent::__construct($user);
    }

    public function toArray(Request $request): array
    {
        return [
            'user'  => new UserResource($this->user),
            'token' => $this->token,
        ];
    }
}
