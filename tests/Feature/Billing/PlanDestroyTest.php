<?php

namespace Tests\Feature\Billing;

use App\Exceptions\ApiException;
use App\Models\Checkout;
use App\Models\Plan;
use App\Models\User;
use App\Services\Plan\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_toggles_a_plan_price_status(): void
    {
        $price = Plan::create(['code' => 'basic', 'name' => 'Básico', 'active' => true])
            ->prices()
            ->create([
                'code' => 'basic-1m-v1',
                'version' => 1,
                'interval_months' => 1,
                'amount_cents' => 2990,
                'currency' => 'BRL',
                'active' => true,
            ]);

        $service = app(PlanService::class);

        $this->assertFalse($service->changePriceStatus($price)->active);
        $this->assertTrue($service->changePriceStatus($price)->active);
    }

    public function test_it_permanently_removes_a_plan_and_its_prices(): void
    {
        $plan = Plan::create(['code' => 'basic', 'name' => 'Básico', 'active' => true]);
        $price = $plan->prices()->create([
            'code' => 'basic-1m-v1',
            'version' => 1,
            'interval_months' => 1,
            'amount_cents' => 2990,
            'currency' => 'BRL',
            'active' => true,
        ]);

        app(PlanService::class)->destroy($plan);

        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
        $this->assertDatabaseMissing('plan_prices', ['id' => $price->id]);
    }

    public function test_it_does_not_remove_a_plan_with_a_linked_checkout(): void
    {
        $plan = Plan::create(['code' => 'basic', 'name' => 'Básico', 'active' => true]);
        $price = $plan->prices()->create([
            'code' => 'basic-1m-v1',
            'version' => 1,
            'interval_months' => 1,
            'amount_cents' => 2990,
            'currency' => 'BRL',
            'active' => true,
        ]);

        $this->expectException(ApiException::class);

        $user = User::create([
            'username' => 'buyer',
            'email' => 'buyer@example.test',
            'password' => 'password',
            'phone' => '11999999999',
            'user_type' => 'user',
        ]);
        Checkout::create([
            'user_id' => $user->id,
            'plan_price_id' => $price->id,
            'type' => 'initial',
            'status' => 'open',
            'amount_cents' => 2990,
            'currency' => 'BRL',
            'client_idempotency_key' => (string) str()->uuid(),
            'expires_at' => now()->addHour(),
        ]);

        app(PlanService::class)->destroy($plan);
    }
}
