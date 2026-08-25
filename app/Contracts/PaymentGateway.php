<?php

namespace App\Contracts;

use App\Data\GatewayPayment;
use App\Models\Payment;

interface PaymentGateway
{
    public function createPix(Payment $payment, array $payer): GatewayPayment;

    public function get(string $externalId): GatewayPayment;
}
