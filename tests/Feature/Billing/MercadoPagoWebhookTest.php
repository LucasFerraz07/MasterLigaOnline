<?php

namespace Tests\Feature\Billing;

use App\Contracts\PaymentGateway;
use App\Data\GatewayPayment;
use App\Models\PaymentWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakePaymentGateway;
use Tests\TestCase;

class MercadoPagoWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_signature_is_rejected(): void
    {
        config(['services.mercado_pago.webhook_secret' => 'secret']);
        $this->postJson('/api/webhooks/mercado-pago?data.id=123', ['id' => 'event-1', 'type' => 'payment', 'data' => ['id' => '123']], ['x-signature' => 'invalid', 'x-request-id' => 'req'])->assertUnauthorized();
        $this->assertDatabaseCount('payment_webhook_events', 0);
    }

    public function test_valid_duplicate_notification_is_stored_once_and_reconciled(): void
    {
        config(['services.mercado_pago.webhook_secret' => 'secret']);
        $fake = new FakePaymentGateway;
        $fake->payments['123'] = new GatewayPayment('123', 'pending', 'pending_waiting_transfer', 1000, 'BRL', 'pix', 'unknown');
        $this->app->instance(PaymentGateway::class, $fake);
        $ts = (string) time();
        $requestId = 'req-123';
        $dataId = '123';
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $signature = 'ts='.$ts.',v1='.hash_hmac('sha256', $manifest, 'secret');
        $body = ['id' => 'event-1', 'type' => 'payment', 'data' => ['id' => $dataId]];
        $headers = ['x-signature' => $signature, 'x-request-id' => $requestId];
        $this->postJson('/api/webhooks/mercado-pago?data.id=123', $body, $headers)->assertOk();
        $this->postJson('/api/webhooks/mercado-pago?data.id=123', $body, $headers)->assertOk();
        $this->assertDatabaseCount('payment_webhook_events', 1);
        $this->assertSame('ignored', PaymentWebhookEvent::first()->status->value);
        $this->assertSame(1,$fake->getCalls);
    }
}
