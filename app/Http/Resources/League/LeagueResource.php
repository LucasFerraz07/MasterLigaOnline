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
            'name' => (string) $this->name,
            'silver_limit' => $this->silver_limit !== null ? (int) $this->silver_limit : null,
            'golden_limit'  => $this->golden_limit !== null ? (int) $this->golden_limit : null,
            'black_limit' => $this->black_limit !== null ? (int) $this->black_limit : null,
            'mulct_contract_limit' => (int) $this->mulct_contract_limit,
            'player_limit' => $this->player_limit !== null ? (int) $this->player_limit : null,
            'subscription_id' => $this->subscription_id,
            'subscription_start' => (string) $this->subscription_start,
            'subscription_end' => (string) $this->subscription_end,
            'is_active' => (bool) $this->is_active,
            'owner' => OwnerResource::make($this->whenLoaded('owners')),
            'subscription' => SubscriptionResource::make($this->whenLoaded('subscription')),
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
        ];
    }
}
