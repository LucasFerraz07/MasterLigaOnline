<?php

namespace App\Http\Resources\TransferBid;

use App\Http\Resources\User\SimplifiedUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferBidResource extends JsonResource
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
            'proposer' => SimplifiedUserResource::make($this->whenLoaded('proposer')),
            'receiver' => SimplifiedUserResource::make($this->whenLoaded('receiver')),
            'status' => $this->status?->value,
            'items' => TransferBidItemResource::collection($this->whenLoaded('items')),
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
        ];
    }
}
