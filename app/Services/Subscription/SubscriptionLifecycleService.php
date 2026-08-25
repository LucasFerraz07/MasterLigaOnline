<?php

namespace App\Services\Subscription;

use App\Enums\CheckoutStatus;
use App\Enums\CheckoutType;
use App\Enums\LeagueSubscriptionStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionPeriodStatus;
use App\Models\Checkout;
use App\Models\League;
use App\Models\LeagueSubscription;
use App\Models\Payment;
use App\Models\SubscriptionPeriod;
use App\Models\User;
use App\Services\League\LeagueService;
use Illuminate\Support\Facades\DB;

class SubscriptionLifecycleService
{
    public function __construct(private readonly LeagueService $leagueService) {}

    public function fulfill(string $paymentId): void
    {
        DB::transaction(function () use ($paymentId) {
            $payment = Payment::with('checkout.planPrice.plan')->lockForUpdate()->findOrFail($paymentId);
            if ($payment->status !== PaymentStatus::APPROVED || $payment->subscriptionPeriod()->exists()) {
                return;
            }
            $checkout = Checkout::lockForUpdate()->findOrFail($payment->checkout_id);
            if ($checkout->fulfilled_at || $checkout->payments()->where('id', '!=', $payment->id)->where('status', PaymentStatus::APPROVED->value)->exists()) {
                $payment->update(['status' => PaymentStatus::REQUIRES_ACTION, 'status_detail' => 'duplicate_approved_payment']);

                return;
            }
            $user = User::lockForUpdate()->findOrFail($checkout->user_id);
            $price = $payment->checkout->planPrice;
            $now = now();

            if ($checkout->type === CheckoutType::INITIAL) {
                if ($user->league_id !== null) {
                    $payment->update(['status' => PaymentStatus::REQUIRES_ACTION, 'status_detail' => 'user_already_has_league']);
                    $checkout->update(['status' => CheckoutStatus::REQUIRES_ACTION]);

                    return;
                }
                $league = $this->leagueService->provisionLeagueForUser($user, [
                    'name' => $checkout->league_name,
                    'owner' => ['full_name' => $checkout->owner_full_name],
                ]);
                $subscription = LeagueSubscription::create([
                    'user_id' => $user->id, 'league_id' => $league->id,
                    'current_plan_id' => $price->plan_id, 'current_plan_price_id' => $price->id,
                    'status' => LeagueSubscriptionStatus::ACTIVE, 'collection_mode' => 'manual',
                    'gateway' => 'mercado_pago', 'current_period_starts_at' => $now,
                    'current_period_ends_at' => $now->copy()->addMonthsNoOverflow($price->interval_months),
                    'access_expires_at' => $now->copy()->addMonthsNoOverflow($price->interval_months),
                ]);
                $start = $now;
            } else {
                $league = League::lockForUpdate()->findOrFail($checkout->league_id);
                $subscription = LeagueSubscription::lockForUpdate()->where('league_id', $league->id)->firstOrFail();
                $start = $subscription->access_expires_at->isFuture() ? $subscription->access_expires_at->copy() : $now;
            }
            $end = $start->copy()->addMonthsNoOverflow($price->interval_months);
            $periodStatus = $start->isFuture() ? SubscriptionPeriodStatus::SCHEDULED : SubscriptionPeriodStatus::ACTIVE;
            SubscriptionPeriod::create([
                'league_subscription_id' => $subscription->id, 'plan_id' => $price->plan_id,
                'plan_price_id' => $price->id, 'payment_id' => $payment->id,
                'amount_cents' => $payment->amount_cents, 'currency' => $payment->currency,
                'starts_at' => $start, 'ends_at' => $end, 'status' => $periodStatus,
            ]);
            $updates = ['access_expires_at' => $end, 'status' => LeagueSubscriptionStatus::ACTIVE, 'gateway' => 'mercado_pago'];
            if ($periodStatus === SubscriptionPeriodStatus::ACTIVE) {
                $updates += ['current_plan_id' => $price->plan_id, 'current_plan_price_id' => $price->id, 'current_period_starts_at' => $start, 'current_period_ends_at' => $end];
            }
            $subscription->update($updates);
            $league->update(['deactivated_at' => null]);
            $checkout->update(['league_id' => $league->id, 'status' => CheckoutStatus::FULFILLED, 'paid_at' => $checkout->paid_at ?? $now, 'fulfilled_at' => $now]);
        });
    }

    public function revoke(string $paymentId): void
    {
        DB::transaction(function () use ($paymentId) {
            $payment = Payment::lockForUpdate()->findOrFail($paymentId);
            $period = SubscriptionPeriod::where('payment_id', $payment->id)->lockForUpdate()->first();
            if (! $period || $period->status === SubscriptionPeriodStatus::REVOKED) {
                return;
            }
            $period->update(['status' => SubscriptionPeriodStatus::REVOKED]);
            $this->syncLocked(LeagueSubscription::lockForUpdate()->findOrFail($period->league_subscription_id));
        });
    }

    public function sync(LeagueSubscription $subscription): void
    {
        DB::transaction(fn () => $this->syncLocked(LeagueSubscription::lockForUpdate()->findOrFail($subscription->id)));
    }

    private function syncLocked(LeagueSubscription $subscription): void
    {
        $now = now();
        $periods = $subscription->periods()->where('status', '!=', SubscriptionPeriodStatus::REVOKED->value)->orderBy('starts_at')->get();
        foreach ($periods as $period) {
            $status = $period->ends_at->lte($now) ? SubscriptionPeriodStatus::EXPIRED
                : ($period->starts_at->lte($now) ? SubscriptionPeriodStatus::ACTIVE : SubscriptionPeriodStatus::SCHEDULED);
            if ($period->status !== $status) {
                $period->update(['status' => $status]);
            }
        }
        $current = $periods->first(fn ($p) => $p->starts_at->lte($now) && $p->ends_at->gt($now));
        $lastEnd = $periods->max('ends_at');
        if (! $current) {
            $subscription->update(['status' => LeagueSubscriptionStatus::EXPIRED, 'access_expires_at' => $lastEnd ?? $now]);
            $subscription->league()->update(['deactivated_at' => $now]);

            return;
        }
        $subscription->update([
            'status' => LeagueSubscriptionStatus::ACTIVE, 'current_plan_id' => $current->plan_id,
            'current_plan_price_id' => $current->plan_price_id, 'current_period_starts_at' => $current->starts_at,
            'current_period_ends_at' => $current->ends_at, 'access_expires_at' => $lastEnd,
        ]);
        $subscription->league()->update(['deactivated_at' => null]);
    }
}
