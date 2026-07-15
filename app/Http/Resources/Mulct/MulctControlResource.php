<?php

namespace App\Http\Resources\Mulct;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MulctControlResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'season_id' => $this->season_id,
            'buyouts_purchased' => (int) $this->buyouts_purchased,
            'buyouts_lost' => (int) $this->buyouts_lost,
            'limit' => (int) $this->limit,
        ];
    }
}
