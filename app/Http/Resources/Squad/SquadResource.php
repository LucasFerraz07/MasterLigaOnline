<?php

namespace App\Http\Resources\Squad;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'user' => [
                'id' => $this->user_id,
                'username' => (string) $this->owner_username,
            ],
            'player' => [
                'id' => $this->player_id,
                'name' => (string) $this->player_name,
                'overall' => (int) $this->player_overall,
                'position' => (string) $this->player_position,
                'category' => $this->player_category !== null ? (string) $this->player_category : null,
                'image_url' => $this->player_image_path ? Storage::disk('public')->url($this->player_image_path) : null,
            ],
            'season_id' => $this->season_id,
            'acquisition_type' => $this->acquisition_type?->value,
            'salary' => (string) $this->salary,
            'passe' => (string) $this->passe,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
        ];
    }
}
