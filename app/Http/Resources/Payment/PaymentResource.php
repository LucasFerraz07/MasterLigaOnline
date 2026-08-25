<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'gateway' => $this->gateway, 'method' => $this->method,
            'status' => $this->status?->value, 'status_detail' => $this->status_detail,
            'amount_cents' => $this->amount_cents, 'amount' => (float) number_format($this->amount_cents / 100, 2, '.', ''),
            'currency' => $this->currency, 'expires_at' => $this->expires_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'pix' => ['qr_code_base64' => $this->pix_qr_code_base64, 'copy_paste_code' => $this->pix_copy_paste_code, 'ticket_url' => $this->pix_ticket_url],
        ];
    }
}
