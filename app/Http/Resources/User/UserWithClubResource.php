<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Club\ClubResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserWithClubResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $club = $this->relationLoaded('clubIdentity') ? $this->clubIdentity?->club : null;

        return [
            'id' => $this->id,
            'username' => (string) $this->username,
            'phone' => (string) $this->phone,
            'club' => $club ? ClubResource::make($club) : null,
        ];
    }
}