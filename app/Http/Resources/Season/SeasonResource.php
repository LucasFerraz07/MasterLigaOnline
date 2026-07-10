<?php

namespace App\Http\Resources\Season;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeasonResource extends JsonResource
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
            'season_number' => $this->season_number,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'phase' => $this->phase,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
