<?php

namespace App\Http\Resources\League;

use App\Http\Resources\Owner\OwnerResource;
use App\Http\Resources\PlanResource;
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
            'golden_limit' => $this->golden_limit !== null ? (int) $this->golden_limit : null,
            'black_limit' => $this->black_limit !== null ? (int) $this->black_limit : null,
            'mulct_contract_limit' => (int) $this->mulct_contract_limit,
            'player_limit' => $this->player_limit !== null ? (int) $this->player_limit : null,
            'win_credit' => (string) $this->win_credit,
            'draw_credit' => (string) $this->draw_credit,
            'loss_credit' => (string) $this->loss_credit,
            'is_active' => (bool) $this->is_active,
            'owner' => OwnerResource::make($this->whenLoaded('owners')),
            'subscription' => $this->whenLoaded('leagueSubscription', fn () => [
                'id' => $this->leagueSubscription->id,
                'status' => $this->leagueSubscription->status->value,
                'access_expires_at' => $this->leagueSubscription->access_expires_at->toISOString(),
                'plan' => PlanResource::make($this->leagueSubscription->currentPlan),
            ]),
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
        ];
    }
}
