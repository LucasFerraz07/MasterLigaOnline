<?php

namespace App\Http\Resources\ClubIdentity;

use App\Http\Resources\Club\ClubResource;
use App\Http\Resources\User\SimplifiedUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClubIdentityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'league_id' => $this->league_id,
            'user' => SimplifiedUserResource::make($this->whenLoaded('user')),
            'club' => ClubResource::make($this->whenLoaded('club')),
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
        ];
    }
}
