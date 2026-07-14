<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'username'   => (string) $this->username,
            'email'      => (string) $this->email,
            'phone'      => (string) $this->phone,
            'league_id'  => $this->league_id,
            'balance'    => (string) $this->balance,
            'user_type'  => (string) $this->user_type->value,
            'roles'      => $this->roles->pluck('name')->all(),
            'permissions' => $this->roles->flatMap(fn ($role) => $role->permissions->pluck('name'))->unique()->values()->all(),
            'is_active'  => (bool) $this->is_active,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
        ];
    }
}
