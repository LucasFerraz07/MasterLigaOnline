<?php

namespace App\Http\Resources\TransferBid;

use App\Enums\TransferItemType;
use App\Http\Resources\Player\PlayerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferBidItemResource extends JsonResource
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
            'side' => $this->side?->value,
            'item_type' => $this->item_type?->value,
            'player' => $this->item_type === TransferItemType::Player ? PlayerResource::make($this->whenLoaded('player')) : null,
            'amount' => $this->amount !== null ? (string) $this->amount : null,
        ];
    }
}
