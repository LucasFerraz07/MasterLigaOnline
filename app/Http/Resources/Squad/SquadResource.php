<?php

namespace App\Http\Resources\Squad;

use App\Http\Resources\Player\PlayerResource;
use App\Http\Resources\User\SimplifiedUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SquadResource extends JsonResource
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
            'player' => PlayerResource::make($this->whenLoaded('player')),
            'acquisition_type' => $this->acquisition_type?->value,
            'salary' => (string) $this->salary,
            'passe' => (string) $this->passe,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
        ];
    }
}
