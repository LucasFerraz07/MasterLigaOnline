<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Events\PaymentApproved;
use App\Events\PaymentRefunded;
use App\Listeners\FulfillApprovedPayment;
use App\Listeners\RevokeRefundedPayment;
use App\Services\Payment\MercadoPagoPaymentGateway;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, MercadoPagoPaymentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(PaymentApproved::class, FulfillApprovedPayment::class);
        Event::listen(PaymentRefunded::class, RevokeRefundedPayment::class);
    }
}
