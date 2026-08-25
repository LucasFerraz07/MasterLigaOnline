<?php

namespace App\Jobs;

use App\Contracts\PaymentGateway;
use App\Enums\WebhookEventStatus;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Services\PaymentReconciliationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessMercadoPagoWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [10, 30, 120, 600];

    public function __construct(public readonly string $eventId) {}

    public function handle(PaymentGateway $gateway, PaymentReconciliationService $reconciliation): void
    {
        $event = PaymentWebhookEvent::findOrFail($this->eventId);
        if ($event->status === WebhookEventStatus::PROCESSED || $event->status === WebhookEventStatus::IGNORED) {
            return;
        }
        $event->update(['status' => WebhookEventStatus::PROCESSING, 'attempts' => $event->attempts + 1, 'last_error' => null]);
        try {
            $remote = $gateway->get($event->resource_id);
            $payment = Payment::where('external_id', $remote->id)->first();
            if (! $payment && $remote->externalReference) {
                $payment = Payment::find($remote->externalReference);
            }
            if (! $payment) {
                $event->update(['status' => WebhookEventStatus::IGNORED, 'processed_at' => now()]);

                return;
            }
            $reconciliation->apply($payment, $remote);
            $event->update(['status' => WebhookEventStatus::PROCESSED, 'processed_at' => now()]);
        } catch (\Throwable $e) {
            $event->update([
                'status' => WebhookEventStatus::FAILED,
                'last_error' => class_basename($e).':'.(string) $e->getCode(),
            ]);
            Log::warning('mercado_pago.webhook.processing_failed', ['event_id' => $event->id, 'resource_id' => $event->resource_id, 'attempt' => $event->attempts]);
            throw $e;
        }
    }
}
