<?php

namespace App\Http\Resources\League;

use App\Http\Resources\Owner\OwnerResource;
use App\Http\Resources\Subscription\SubscriptionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeagueResource extends JsonResource
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
            'name' => $this->name,
            'silver_limit' => $this->silver_limit,
            'golden_limit'  => $this->golden_limit,
            'black_limit' => $this->black_limit,
            'mulct_contract_limit' => $this->mulct_contract_limit,
            'player_limit' => $this->player_limit,
            'subscription_id' => $this->subscription_id,
            'subscription_start' => $this->subscription_start,
            'subscription_end' => $this->subscription_end,
            'is_active' => $this->is_active,
            'owner' => OwnerResource::make($this->whenLoaded('owners')),
            'subscription' => SubscriptionResource::make($this->whenLoaded('subscription')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
