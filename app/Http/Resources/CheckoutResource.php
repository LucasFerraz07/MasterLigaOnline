<?php

namespace App\Http\Resources;

use App\Http\Resources\Payment\PaymentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $payment = $this->relationLoaded('payments') ? $this->payments->sortByDesc('created_at')->first() : null;

        return ['checkout_id' => $this->id, 'type' => $this->type?->value, 'status' => $this->status?->value, 'league_id' => $this->league_id, 'plan_price_id' => $this->plan_price_id, 'amount_cents' => $this->amount_cents, 'amount' => (float) number_format($this->amount_cents / 100, 2, '.', ''), 'currency' => $this->currency, 'expires_at' => $this->expires_at?->toISOString(), 'payment_configuration' => ['payment_methods' => ['bankTransfer' => 'pix']], 'payment' => $payment ? new PaymentResource($payment) : null];
    }
}
