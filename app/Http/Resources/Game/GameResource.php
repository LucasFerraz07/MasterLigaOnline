<?php

namespace App\Http\Resources\Game;

use App\Http\Resources\User\SimplifiedUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameResource extends JsonResource
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
            'season_id' => $this->season_id,
            'home_user' => SimplifiedUserResource::make($this->whenLoaded('homeUser')),
            'away_user' => $this->away_user_id !== null ? SimplifiedUserResource::make($this->whenLoaded('awayUser')) : null,
            'home_goals' => (int) $this->home_goals,
            'away_goals' => (int) $this->away_goals,
            'round' => (int) $this->round,
            'half' => (int) $this->half,
            'is_bye' => (bool) $this->is_bye,
            'match_date' => $this->match_date?->toDateString(),
            'status' => $this->status?->value,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
        ];
    }
}
