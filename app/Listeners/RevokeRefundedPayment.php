<?php

namespace App\Listeners;

use App\Events\PaymentRefunded;
use App\Services\SubscriptionLifecycleService;

class RevokeRefundedPayment
{
    public function __construct(private readonly SubscriptionLifecycleService $service) {}

    public function handle(PaymentRefunded $event): void
    {
        $this->service->revoke($event->paymentId);
    }
}
