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
            'season_number' => (int) $this->season_number,
            'start_date' => (string) $this->start_date,
            'end_date' => $this->end_date !== null ? (string) $this->end_date : null,
            'status' => (string) $this->status->value,
            'phase' => (string) $this->phase->value,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
        ];
    }
}
