<?php

namespace App\Http\Resources\Company;

use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OwnerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'cpf'  => $this->cpf,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
