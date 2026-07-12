<?php

namespace App\Http\Resources\Transfer;

use App\Http\Resources\Player\PlayerResource;
use App\Http\Resources\User\SimplifiedUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
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
            'player' => PlayerResource::make($this->whenLoaded('player')),
            'seller' => $this->seller_id !== null ? SimplifiedUserResource::make($this->whenLoaded('seller')) : null,
            'buyer' => SimplifiedUserResource::make($this->whenLoaded('buyer')),
            'type' => $this->type?->value,
            'amount' => (string) $this->amount,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
        ];
    }
}