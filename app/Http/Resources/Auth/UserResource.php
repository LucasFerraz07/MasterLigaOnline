<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => (string) $this->email,
            'phone'=> (string) $this->phone,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
