<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\CheckoutStatus;
use App\Enums\CheckoutType;
use App\Enums\PaymentStatus;
use App\Exceptions\ApiException;
use App\Models\Checkout;
use App\Models\PlanPrice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use MercadoPago\Exceptions\MPApiException;

class CheckoutService
{
    public function __construct(private readonly PaymentGateway $gateway, private readonly PaymentReconciliationService $reconciliation) {}

    public function create(User $user, array $data): Checkout
    {
        return DB::transaction(function () use ($user, $data) {
            $user = User::lockForUpdate()->findOrFail($user->id);
            $existing = Checkout::where('user_id', $user->id)->where('client_idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                $sameProvisioningData = $existing->plan_price_id === $data['plan_price_id']
                    && ($existing->type !== CheckoutType::INITIAL
                        || ($existing->league_name === ($data['league_name'] ?? null)
                            && $existing->owner_full_name === ($data['owner_full_name'] ?? null)));

                if (! $sameProvisioningData) {
                    throw new ApiException('A chave de idempotência já foi usada com outros dados.', 409);
                }

                return $existing->load('payments');
            }
            $price = PlanPrice::with('plan')->whereKey($data['plan_price_id'])->where('active', true)->firstOrFail();
            if (! $price->plan->active) {
                throw new ApiException('Plano indisponível.', 422);
            }
            $type = $user->league_id ? CheckoutType::RENEWAL : CheckoutType::INITIAL;
            if ($type === CheckoutType::INITIAL && (empty($data['league_name']) || empty($data['owner_full_name']))) {
                throw new ApiException('Nome da liga e responsável são obrigatórios na contratação inicial.', 422);
            }
            if ($type === CheckoutType::RENEWAL && ! $user->leagueSubscription) {
                throw new ApiException('A liga não possui assinatura para renovação.', 409);
            }

            return Checkout::create(['user_id' => $user->id, 'league_id' => $user->league_id, 'plan_price_id' => $price->id, 'type' => $type, 'status' => CheckoutStatus::OPEN, 'league_name' => $type === CheckoutType::INITIAL ? $data['league_name'] : null, 'owner_full_name' => $type === CheckoutType::INITIAL ? $data['owner_full_name'] : null, 'amount_cents' => $price->amount_cents, 'currency' => $price->currency, 'client_idempotency_key' => $data['idempotency_key'], 'expires_at' => now()->addHour()])->load('payments');
        });
    }

    public function show(User $user, Checkout $checkout): Checkout
    {
        if ($checkout->user_id !== $user->id && ! $user->hasRole('system_admin')) {
            throw new ApiException('Checkout não encontrado.', 404);
        }
        if ($checkout->status === CheckoutStatus::OPEN && $checkout->expires_at->isPast()) {
            $checkout->update(['status' => CheckoutStatus::EXPIRED]);
        }

        return $checkout->load('payments');
    }

    public function pay(User $user, Checkout $checkout, array $data): Checkout
    {
        if ($checkout->user_id !== $user->id) {
            throw new ApiException('Checkout não encontrado.', 404);
        }
        $payment = DB::transaction(function () use ($checkout, $data) {
            $checkout = Checkout::lockForUpdate()->findOrFail($checkout->id);
            $existing = $checkout->payments()->where('client_idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                return $existing;
            }
            if ($checkout->expires_at->isPast()) {
                $checkout->update(['status' => CheckoutStatus::EXPIRED]);
                throw new ApiException('Checkout expirado.', 422);
            }
            if (in_array($checkout->status, [CheckoutStatus::FULFILLED, CheckoutStatus::CANCELED], true)) {
                throw new ApiException('Checkout não aceita novos pagamentos.', 409);
            }
            $pending = $checkout->payments()->whereIn('status', [PaymentStatus::CREATING->value, PaymentStatus::PENDING->value, PaymentStatus::IN_PROCESS->value, PaymentStatus::UNKNOWN->value])->latest()->first();
            if ($pending && (! $pending->expires_at || $pending->expires_at->isFuture())) {
                return $pending;
            }
            $checkout->update(['status' => CheckoutStatus::PAYMENT_PENDING]);

            return $checkout->payments()->create(['gateway' => 'mercado_pago', 'method' => 'pix', 'amount_cents' => $checkout->amount_cents, 'currency' => $checkout->currency, 'status' => PaymentStatus::CREATING, 'client_idempotency_key' => $data['idempotency_key'], 'gateway_idempotency_key' => (string) Str::uuid()]);
        });
        if (! in_array($payment->status, [PaymentStatus::CREATING, PaymentStatus::UNKNOWN], true)) {
            return $checkout->refresh()->load('payments');
        }

        try {
            $payment->load('checkout');
            $remote = $this->gateway->createPix($payment, ['email' => $user->email, 'identification' => $data['payment']['payer']['identification']]);
        } catch (MPApiException $e) {
            $retryable = $e->getStatusCode() === 429 || $e->getStatusCode() >= 500;
            Log::warning($retryable ? 'mercado_pago.payment.unknown' : 'mercado_pago.payment.rejected', [
                'checkout_id' => $checkout->id,
                'payment_id' => $payment->id,
                'gateway_status' => $e->getStatusCode(),
                'exception' => $e::class,
            ]);
            $payment->update([
                'status' => $retryable ? PaymentStatus::UNKNOWN : PaymentStatus::REJECTED,
                'status_detail' => $retryable ? 'gateway_unavailable' : 'gateway_rejected',
            ]);

            return $checkout->refresh()->load('payments');
        } catch (\Throwable $e) {
            Log::warning('mercado_pago.payment.unknown', [
                'checkout_id' => $checkout->id,
                'payment_id' => $payment->id,
                'exception' => $e::class,
            ]);
            $payment->update(['status' => PaymentStatus::UNKNOWN, 'status_detail' => 'gateway_timeout']);

            return $checkout->refresh()->load('payments');
        }

        // A resposta do gateway já foi obtida. Erros de provisionamento não podem
        // reclassificar um pagamento aprovado como timeout/unknown.
        $this->reconciliation->apply($payment, $remote);

        return $checkout->refresh()->load('payments');
    }
}
