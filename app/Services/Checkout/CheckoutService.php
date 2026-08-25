<?php

namespace App\Services\Checkout;

use App\Contracts\PaymentGateway;
use App\Enums\CheckoutStatus;
use App\Enums\CheckoutType;
use App\Enums\PaymentStatus;
use App\Exceptions\ApiException;
use App\Models\Checkout;
use App\Models\Payment;
use App\Models\PlanPrice;
use App\Models\User;
use App\Services\Payment\PaymentReconciliationService;
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
            $apiResponse = $e->getApiResponse();
            Log::warning($retryable ? 'mercado_pago.payment.unknown' : 'mercado_pago.payment.rejected', [
                'provider' => 'mercado_pago',
                'operation' => 'payments.create_pix',
                'checkout_id' => $checkout->id,
                'payment_id' => $payment->id,
                'gateway_http_status' => $apiResponse->getStatusCode(),
                'gateway_error' => $this->mercadoPagoErrorContext($apiResponse->getContent()),
                'request' => [
                    'amount_cents' => $payment->amount_cents,
                    'currency' => $payment->currency,
                    'payment_method_id' => 'pix',
                    'identification_type' => $data['payment']['payer']['identification']['type'],
                    'payer_email_present' => filled($user->email),
                    'notification_url_configured' => filled(config('services.mercado_pago.notification_url')),
                    'expires_at' => $payment->checkout->expires_at?->toIso8601String(),
                    'gateway_idempotency_key_hash' => $this->idempotencyKeyHash($payment->gateway_idempotency_key),
                ],
                'exception' => $e::class,
            ]);
            $this->markGatewayFailure($payment, $retryable);

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

    /**
     * Extrai somente campos diagnósticos conhecidos da resposta do gateway.
     * O corpo completo pode conter dados do pagador e nunca deve ser registrado.
     *
     * @param array<string,mixed> $body
     * @return array<string,mixed>
     */
    private function mercadoPagoErrorContext(array $body): array
    {
        $causes = $body['cause'] ?? [];
        if (! is_array($causes)) {
            $causes = [];
        }
        if (! array_is_list($causes)) {
            $causes = [$causes];
        }

        return [
            'type' => $this->safeGatewayCode($body['error'] ?? $body['type'] ?? null),
            'code' => $this->safeGatewayCode($body['code'] ?? null),
            'message' => $this->safeGatewayText($body['message'] ?? null),
            'causes' => collect($causes)
                ->filter(fn (mixed $cause) => is_array($cause))
                ->map(fn (array $cause) => array_filter([
                    'type' => $this->safeGatewayCode($cause['type'] ?? null),
                    'code' => $this->safeGatewayCode($cause['code'] ?? null),
                    'message' => $this->safeGatewayText($cause['message'] ?? $cause['description'] ?? null),
                ], fn (mixed $value) => $value !== null))
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function safeGatewayCode(mixed $value): int|string|null
    {
        if (is_int($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return preg_match('/^[a-zA-Z0-9_.-]{1,120}$/', $value) ? $value : null;
    }

    private function safeGatewayText(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $text = trim(strip_tags((string) $value));
        if ($text === '') {
            return null;
        }

        $text = preg_replace([
            '/\b\d{11,14}\b/',
            '/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i',
            '/\b(?:Bearer\s+|APP_USR-|TEST-)[A-Za-z0-9._-]+/i',
        ], ['[redacted-document]', '[redacted-email]', '[redacted-credential]'], $text);

        return Str::limit($text ?? '[redacted]', 500, '...');
    }

    private function idempotencyKeyHash(string $key): string
    {
        return substr(hash_hmac('sha256', $key, (string) config('app.key')), 0, 16);
    }

    private function markGatewayFailure(Payment $payment, bool $retryable): void
    {
        DB::transaction(function () use ($payment, $retryable) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            $payment->update([
                'status' => $retryable ? PaymentStatus::UNKNOWN : PaymentStatus::REJECTED,
                'status_detail' => $retryable ? 'gateway_unavailable' : 'gateway_rejected',
            ]);

            if ($retryable) {
                return;
            }

            $checkout = $payment->checkout()->lockForUpdate()->firstOrFail();
            $hasActivePayment = $checkout->payments()
                ->whereKeyNot($payment->id)
                ->whereIn('status', [
                    PaymentStatus::CREATING->value,
                    PaymentStatus::PENDING->value,
                    PaymentStatus::IN_PROCESS->value,
                    PaymentStatus::UNKNOWN->value,
                ])
                ->exists();

            if (! $hasActivePayment && $checkout->status === CheckoutStatus::PAYMENT_PENDING) {
                $checkout->update([
                    'status' => $checkout->expires_at->isPast()
                        ? CheckoutStatus::EXPIRED
                        : CheckoutStatus::OPEN,
                ]);
            }
        });
    }
}
