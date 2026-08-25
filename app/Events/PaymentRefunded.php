<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class PaymentRefunded
{
    use Dispatchable;

    public function __construct(public readonly string $paymentId) {}
}
