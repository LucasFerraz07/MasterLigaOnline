<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Data\GatewayPayment;
use App\Models\Payment;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Payment as MercadoPagoPayment;

class MercadoPagoPaymentGateway implements PaymentGateway
{
    public function __construct()
    {
        $token = (string) config('services.mercado_pago.access_token');
        if ($token === '') {
            throw new \LogicException('Access Token do Mercado Pago não configurado.');
        }
        MercadoPagoConfig::setAccessToken($token);
    }

    public function createPix(Payment $payment, array $payer): GatewayPayment
    {
        $options = new RequestOptions;
        $options->setCustomHeaders(["X-Idempotency-Key: {$payment->gateway_idempotency_key}"]);
        $remote = (new PaymentClient)->create([
            'transaction_amount' => $payment->amount_cents / 100,
            'description' => "Contratação Master Liga #{$payment->checkout_id}",
            'external_reference' => (string) $payment->id,
            'notification_url' => config('services.mercado_pago.notification_url'),
            'date_of_expiration' => $payment->checkout->expires_at
                ->copy()
                ->setTimezone('America/Sao_Paulo')
                ->format('Y-m-d\\TH:i:s.000P'),
            'payment_method_id' => 'pix',
            'payer' => $payer,
        ], $options);

        return $this->map($remote);
    }

    public function get(string $externalId): GatewayPayment
    {
        return $this->map((new PaymentClient)->get((int) $externalId));
    }

    private function map(MercadoPagoPayment $payment): GatewayPayment
    {
        $tx = $payment->point_of_interaction?->transaction_data;

        return new GatewayPayment(
            id: (string) $payment->id, status: (string) $payment->status,
            statusDetail: $payment->status_detail ? (string) $payment->status_detail : null,
            amountCents: (int) round(((float) $payment->transaction_amount) * 100),
            currency: (string) ($payment->currency_id ?? 'BRL'),
            method: (string) $payment->payment_method_id,
            externalReference: $payment->external_reference ? (string) $payment->external_reference : null,
            qrCodeBase64: $tx?->qr_code_base64, copyPasteCode: $tx?->qr_code,
            ticketUrl: $tx?->ticket_url, expiresAt: $payment->date_of_expiration,
        );
    }
}
