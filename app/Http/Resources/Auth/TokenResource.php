<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TokenResource extends JsonResource
{
    public function __construct(
        private readonly string $token,
        private readonly string $tokenType,
        private readonly int $expiresIn,
    ) {
        parent::__construct(null);
    }

    public function toArray(Request $request): array
    {
        return [
            'token'      => (string) $this->token,
            'token_type' => (string) $this->tokenType,
            'expires_in' => (int) $this->expiresIn,
        ];
    }
}
