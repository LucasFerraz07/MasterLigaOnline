<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = ['checkout_id', 'gateway', 'method', 'amount_cents', 'currency', 'status', 'status_detail', 'client_idempotency_key', 'gateway_idempotency_key', 'external_id', 'pix_qr_code_base64', 'pix_copy_paste_code', 'pix_ticket_url', 'expires_at', 'approved_at', 'refunded_at'];

    protected function casts(): array
    {
        return ['amount_cents' => 'integer', 'status' => PaymentStatus::class, 'expires_at' => 'datetime', 'approved_at' => 'datetime', 'refunded_at' => 'datetime'];
    }

    public function checkout(): BelongsTo
    {
        return $this->belongsTo(Checkout::class);
    }

    public function subscriptionPeriod(): HasOne
    {
        return $this->hasOne(SubscriptionPeriod::class);
    }
}
