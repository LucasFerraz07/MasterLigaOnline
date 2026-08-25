<?php

namespace App\Listeners;

use App\Events\PaymentApproved;
use App\Services\Subscription\SubscriptionLifecycleService;

class FulfillApprovedPayment
{
    public function __construct(private readonly SubscriptionLifecycleService $service) {}

    public function handle(PaymentApproved $event): void
    {
        $this->service->fulfill($event->paymentId);
    }
}
