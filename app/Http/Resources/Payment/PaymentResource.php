<?php

namespace App\Http\Resources\Payment;

use App\Http\Resources\Subscription\SubscriptionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'external_id' => $this->external_id,
            'status' => $this->status?->value,
            'months' => (int) $this->months,
            'amount' => (string) $this->amount,
            'league_name' => (string) $this->league_name,
            'league_id' => $this->league_id,
            'pix_qr_code' => $this->pix_qr_code,
            'pix_br_code' => $this->pix_br_code,
            'expires_at' => $this->expires_at?->toISOString(),
            'subscription' => SubscriptionResource::make($this->whenLoaded('subscription')),
            'created_at' => (string) $this->created_at,
        ];
    }
}
