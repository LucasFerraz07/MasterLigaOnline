<?php

namespace App\Models;

use App\Enums\WebhookEventStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PaymentWebhookEvent extends Model
{
    use HasUuids;

    protected $fillable = ['provider', 'provider_event_id', 'resource_type', 'resource_id', 'request_id', 'payload_hash', 'payload', 'status', 'attempts', 'last_error', 'processed_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'status' => WebhookEventStatus::class, 'attempts' => 'integer', 'processed_at' => 'datetime'];
    }
}
