<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $monthly = $this->plan?->prices?->firstWhere('interval_months', 1);
        $reference = $monthly ? $monthly->amount_cents * $this->interval_months : null;

        return [
            'id' => $this->id, 'code' => $this->code, 'version' => $this->version,
            'interval_months' => $this->interval_months, 'amount_cents' => $this->amount_cents,
            'amount' => number_format($this->amount_cents / 100, 2, '.', ''),
            'currency' => $this->currency, 'monthly_equivalent_cents' => (int) round($this->amount_cents / $this->interval_months),
            'discount_percent' => $reference && $reference > 0 ? round((1 - $this->amount_cents / $reference) * 100, 2) : null,
            'active' => $this->active,
        ];
    }
}
