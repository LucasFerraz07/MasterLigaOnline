<?php

namespace Tests\Fakes;

use App\Contracts\PaymentGateway;
use App\Data\GatewayPayment;
use App\Models\Payment;

class FakePaymentGateway implements PaymentGateway
{
    public string $status = 'approved';

    public int $calls = 0;

    public int $getCalls = 0;

    public int $failuresRemaining = 0;

    private int $sequence = 1000;

    /** @var array<string,GatewayPayment> */
    public array $payments = [];

    /** @var array<string,GatewayPayment> */
    private array $paymentsByIdempotencyKey = [];

    public function createPix(Payment $payment, array $payer): GatewayPayment
    {
        $this->calls++;
        if ($this->failuresRemaining > 0) {
            $this->failuresRemaining--;
            throw new \RuntimeException('Timeout simulado.');
        }
        if (isset($this->paymentsByIdempotencyKey[$payment->gateway_idempotency_key])) {
            return $this->paymentsByIdempotencyKey[$payment->gateway_idempotency_key];
        }
        $id = (string) ++$this->sequence;

        return $this->payments[$id] = $this->paymentsByIdempotencyKey[$payment->gateway_idempotency_key] = new GatewayPayment($id, $this->status, $this->status === 'approved' ? 'accredited' : 'pending_waiting_transfer', $payment->amount_cents, $payment->currency, 'pix', $payment->id, 'base64', 'pix-code', 'https://example.test/pix', $payment->checkout->expires_at->toIso8601String());
    }

    public function get(string $externalId): GatewayPayment
    {
        $this->getCalls++;

        return $this->payments[$externalId];
    }
}
