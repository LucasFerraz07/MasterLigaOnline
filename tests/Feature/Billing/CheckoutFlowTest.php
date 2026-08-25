<?php

namespace Tests\Feature\Billing;

use App\Contracts\PaymentGateway;
use App\Data\GatewayPayment;
use App\Enums\CheckoutStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionPeriodStatus;
use App\Enums\UserType;
use App\Events\PaymentApproved;
use App\Exceptions\ApiException;
use App\Models\Club;
use App\Models\Plan;
use App\Models\User;
use App\Services\Checkout\CheckoutService;
use App\Services\Payment\PaymentReconciliationService;
use App\Services\Plan\PlanService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\Net\MPResponse;
use Tests\Fakes\FakePaymentGateway;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    private FakePaymentGateway $gateway;

    private User $user;

    private $price;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Club::create(['name' => 'Clube teste', 'region' => 'national']);
        $plan = Plan::create(['code' => 'basic', 'name' => 'Básico', 'active' => true]);
        $this->price = $plan->prices()->create(['code' => 'basic-2m-v1', 'version' => 1, 'interval_months' => 2, 'amount_cents' => 5490, 'currency' => 'BRL', 'active' => true]);
        $this->user = User::create(['username' => 'buyer', 'email' => 'buyer@example.test', 'password' => 'password', 'phone' => '11999999999', 'user_type' => UserType::USER]);
        $this->gateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);
    }

    public function test_server_uses_catalog_price_and_idempotency(): void
    {
        $service = app(CheckoutService::class);
        $key = (string) str()->uuid();
        $data = ['idempotency_key' => $key, 'plan_price_id' => $this->price->id, 'league_name' => 'Liga', 'owner_full_name' => 'Responsável', 'amount' => 1, 'months' => 24];
        $first = $service->create($this->user, $data);
        $second = $service->create($this->user, $data);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(5490, $first->amount_cents);
    }

    public function test_checkout_idempotency_key_cannot_be_reused_with_other_provisioning_data(): void
    {
        $service = app(CheckoutService::class);
        $key = (string) str()->uuid();
        $service->create($this->user, ['idempotency_key' => $key, 'plan_price_id' => $this->price->id, 'league_name' => 'Liga A', 'owner_full_name' => 'Responsável']);
        $this->expectException(ApiException::class);
        $service->create($this->user, ['idempotency_key' => $key, 'plan_price_id' => $this->price->id, 'league_name' => 'Liga B', 'owner_full_name' => 'Responsável']);
    }

    public function test_gateway_timeout_can_be_retried_with_the_same_keys(): void
    {
        $this->gateway->failuresRemaining = 1;
        $service = app(CheckoutService::class);
        $checkout = $service->create($this->user, ['idempotency_key' => (string) str()->uuid(), 'plan_price_id' => $this->price->id, 'league_name' => 'Liga', 'owner_full_name' => 'Responsável']);
        $payload = ['idempotency_key' => (string) str()->uuid(), 'payment' => ['payment_method_id' => 'pix', 'payer' => ['identification' => ['type' => 'CPF', 'number' => '12345678909']]]];
        $first = $service->pay($this->user, $checkout, $payload);
        $gatewayKey = $first->payments->first()->gateway_idempotency_key;
        $this->assertSame(PaymentStatus::UNKNOWN, $first->payments->first()->status);
        $second = $service->pay($this->user, $checkout, $payload);
        $this->assertSame(CheckoutStatus::FULFILLED, $second->status);
        $this->assertSame($gatewayKey, $second->payments->first()->gateway_idempotency_key);
        $this->assertSame(2, $this->gateway->calls);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_mercado_pago_api_error_is_logged_with_diagnostics_and_without_payer_data(): void
    {
        $logged = [];
        Log::listen(function (MessageLogged $event) use (&$logged): void {
            $logged[] = $event;
        });
        $this->app->instance(PaymentGateway::class, new class implements PaymentGateway
        {
            public function createPix(\App\Models\Payment $payment, array $payer): GatewayPayment
            {
                throw new MPApiException('API error', new MPResponse(422, [
                    'error' => 'bad_request',
                    'message' => 'CPF 19119119100 belongs to buyer@example.test',
                    'cause' => [[
                        'code' => 1234,
                        'description' => 'Invalid CPF 19119119100 for buyer@example.test',
                    ]],
                ]));
            }

            public function get(string $externalId): GatewayPayment
            {
                throw new \LogicException('Not used by this test.');
            }
        });

        $service = app(CheckoutService::class);
        $checkout = $service->create($this->user, [
            'idempotency_key' => (string) str()->uuid(),
            'plan_price_id' => $this->price->id,
            'league_name' => 'Liga',
            'owner_full_name' => 'Responsável',
        ]);

        $result = $service->pay($this->user, $checkout, [
            'idempotency_key' => (string) str()->uuid(),
            'payment' => [
                'payment_method_id' => 'pix',
                'payer' => ['identification' => ['type' => 'CPF', 'number' => '19119119100']],
            ],
        ]);

        $this->assertSame(PaymentStatus::REJECTED, $result->payments->first()->status);
        $this->assertSame(CheckoutStatus::OPEN, $result->status);

        $entry = collect($logged)->first(fn (MessageLogged $event) => $event->message === 'mercado_pago.payment.rejected');
        $this->assertNotNull($entry);
        $this->assertSame(422, $entry->context['gateway_http_status']);
        $this->assertSame('bad_request', $entry->context['gateway_error']['type']);
        $this->assertSame(1234, $entry->context['gateway_error']['causes'][0]['code']);
        $this->assertStringContainsString('[redacted-document]', $entry->context['gateway_error']['message']);
        $this->assertStringContainsString('[redacted-email]', $entry->context['gateway_error']['message']);
        $this->assertStringNotContainsString('19119119100', json_encode($entry->context));
        $this->assertStringNotContainsString('buyer@example.test', json_encode($entry->context));
    }

    public function test_approved_payment_leaves_checkout_paid_until_provisioning_finishes(): void
    {
        Event::fake([PaymentApproved::class]);
        $service = app(CheckoutService::class);
        $checkout = $service->create($this->user, ['idempotency_key' => (string) str()->uuid(), 'plan_price_id' => $this->price->id, 'league_name' => 'Liga', 'owner_full_name' => 'Responsável']);
        $result = $service->pay($this->user, $checkout, ['idempotency_key' => (string) str()->uuid(), 'payment' => ['payment_method_id' => 'pix', 'payer' => ['identification' => ['type' => 'CPF', 'number' => '12345678909']]]]);
        $this->assertSame(CheckoutStatus::PAID, $result->status);
        $this->assertDatabaseCount('leagues', 0);
        Event::assertDispatched(PaymentApproved::class);
    }

    public function test_approved_pix_provisions_once_and_renewal_preserves_time(): void
    {
        $service = app(CheckoutService::class);
        $checkout = $service->create($this->user, ['idempotency_key' => (string) str()->uuid(), 'plan_price_id' => $this->price->id, 'league_name' => 'Liga', 'owner_full_name' => 'Responsável']);
        $paymentKey = (string) str()->uuid();
        $payload = ['idempotency_key' => $paymentKey, 'payment' => ['payment_method_id' => 'pix', 'payer' => ['identification' => ['type' => 'CPF', 'number' => '12345678909']]]];
        $result = $service->pay($this->user, $checkout, $payload);
        $this->assertSame(CheckoutStatus::FULFILLED, $result->status);
        $this->assertDatabaseCount('leagues', 1);
        $this->assertDatabaseCount('league_subscriptions', 1);
        $this->assertDatabaseCount('subscription_periods', 1);
        $service->pay($this->user, $checkout, $payload);
        $this->assertDatabaseCount('subscription_periods', 1);
        $this->user->refresh();
        $renewal = $service->create($this->user, ['idempotency_key' => (string) str()->uuid(), 'plan_price_id' => $this->price->id]);
        $service->pay($this->user, $renewal, ['idempotency_key' => (string) str()->uuid(), 'payment' => $payload['payment']]);
        $periods = $this->user->leagueSubscription->periods()->orderBy('starts_at')->get();
        $this->assertCount(2, $periods);
        $this->assertTrue($periods[1]->starts_at->equalTo($periods[0]->ends_at));
        $this->assertSame(SubscriptionPeriodStatus::SCHEDULED, $periods[1]->status);
    }

    public function test_refund_revokes_access(): void
    {
        $service = app(CheckoutService::class);
        $checkout = $service->create($this->user, ['idempotency_key' => (string) str()->uuid(), 'plan_price_id' => $this->price->id, 'league_name' => 'Liga', 'owner_full_name' => 'Responsável']);
        $result = $service->pay($this->user, $checkout, ['idempotency_key' => (string) str()->uuid(), 'payment' => ['payment_method_id' => 'pix', 'payer' => ['identification' => ['type' => 'CPF', 'number' => '12345678909']]]]);
        $payment = $result->payments->first();
        $remote = new GatewayPayment($payment->external_id, 'refunded', 'refunded', $payment->amount_cents, 'BRL', 'pix', $payment->id);
        app(PaymentReconciliationService::class)->apply($payment, $remote);
        $this->assertSame(PaymentStatus::REFUNDED, $payment->refresh()->status);
        $this->assertSame(SubscriptionPeriodStatus::REVOKED, $payment->subscriptionPeriod->status);
        $this->assertNotNull($this->user->refresh()->league->deactivated_at);
    }

    public function test_gateway_mismatch_requires_action_without_provisioning(): void
    {
        $this->gateway->status = 'pending';
        $service = app(CheckoutService::class);
        $checkout = $service->create($this->user, ['idempotency_key' => (string) str()->uuid(), 'plan_price_id' => $this->price->id, 'league_name' => 'Liga', 'owner_full_name' => 'Responsável']);
        $result = $service->pay($this->user, $checkout, ['idempotency_key' => (string) str()->uuid(), 'payment' => ['payment_method_id' => 'pix', 'payer' => ['identification' => ['type' => 'CPF', 'number' => '12345678909']]]]);
        $payment = $result->payments->first();
        app(PaymentReconciliationService::class)->apply($payment, new GatewayPayment($payment->external_id, 'approved', 'accredited', $payment->amount_cents + 1, 'BRL', 'pix', $payment->id));
        $this->assertSame(PaymentStatus::REQUIRES_ACTION, $payment->refresh()->status);
        $this->assertDatabaseCount('leagues', 0);
    }

    public function test_stale_pending_notification_does_not_regress_an_approved_payment(): void
    {
        $service = app(CheckoutService::class);
        $checkout = $service->create($this->user, ['idempotency_key' => (string) str()->uuid(), 'plan_price_id' => $this->price->id, 'league_name' => 'Liga', 'owner_full_name' => 'Responsável']);
        $result = $service->pay($this->user, $checkout, ['idempotency_key' => (string) str()->uuid(), 'payment' => ['payment_method_id' => 'pix', 'payer' => ['identification' => ['type' => 'CPF', 'number' => '12345678909']]]]);
        $payment = $result->payments->first();

        app(PaymentReconciliationService::class)->apply($payment, new GatewayPayment($payment->external_id, 'pending', 'pending_waiting_transfer', $payment->amount_cents, 'BRL', 'pix', $payment->id));

        $this->assertSame(PaymentStatus::APPROVED, $payment->refresh()->status);
        $this->assertSame(CheckoutStatus::FULFILLED, $checkout->refresh()->status);
    }

    public function test_repeated_approval_retries_a_failed_provisioning(): void
    {
        $this->gateway->status = 'pending';
        $service = app(CheckoutService::class);
        $checkout = $service->create($this->user, ['idempotency_key' => (string) str()->uuid(), 'plan_price_id' => $this->price->id, 'league_name' => 'Liga', 'owner_full_name' => 'Responsável']);
        $result = $service->pay($this->user, $checkout, ['idempotency_key' => (string) str()->uuid(), 'payment' => ['payment_method_id' => 'pix', 'payer' => ['identification' => ['type' => 'CPF', 'number' => '12345678909']]]]);
        $payment = $result->payments->first();
        $payment->update(['status' => PaymentStatus::APPROVED, 'approved_at' => now()]);
        $checkout->update(['status' => CheckoutStatus::PAID, 'paid_at' => now()]);

        app(PaymentReconciliationService::class)->apply($payment, new GatewayPayment($payment->external_id, 'approved', 'accredited', $payment->amount_cents, 'BRL', 'pix', $payment->id));

        $this->assertSame(CheckoutStatus::FULFILLED, $checkout->refresh()->status);
        $this->assertDatabaseCount('subscription_periods', 1);
    }

    public function test_new_price_version_deactivates_previous_price(): void
    {
        $new = app(PlanService::class)->addPrice($this->price->plan, ['code' => 'basic-2m-v2', 'interval_months' => 2, 'amount_cents' => 4990]);
        $this->assertFalse($this->price->refresh()->active);
        $this->assertTrue($new->active);
        $this->assertSame(2, $new->version);
    }
}
