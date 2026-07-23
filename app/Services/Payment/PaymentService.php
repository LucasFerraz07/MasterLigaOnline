<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionPeriodStatus;
use App\Enums\UserType;
use App\Exceptions\ApiException;
use App\Http\Resources\Payment\PaymentResource;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\Subscription;
use App\Models\SubscriptionPeriod;
use App\Services\League\LeagueService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly AbacatePayGatewayService $gateway,
        private readonly LeagueService $leagueService,
    ) {}

    public function store(array $data): PaymentResource
    {
        return DB::transaction(function () use ($data): PaymentResource {
            $user = Auth::user();

            if ($user->league_id !== null) {
                throw new ApiException('Este usuário já possui uma liga.', 409);
            }

            $subscription = Subscription::findOrFail($data['subscription_id']);
            $months = (int) $data['months'];

            $payment = Payment::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'months' => $months,
                'league_name' => $data['league_name'],
                'owner_full_name' => $data['owner_full_name'],
                'amount' => $subscription->price * $months,
                'status' => PaymentStatus::PENDING,
            ]);

            $charge = $this->gateway->createPixCharge($payment);

            $payment->update([
                'external_id' => $charge['id'] ?? null,
                'pix_qr_code' => $charge['brCodeBase64'] ?? null,
                'pix_br_code' => $charge['brCode'] ?? null,
                'expires_at' => isset($charge['expiresAt']) ? Carbon::parse($charge['expiresAt']) : null,
            ]);

            $payment->load('subscription');

            return new PaymentResource($payment);
        });
    }

    public function show(array $data): PaymentResource
    {
        $actor = Auth::user();
        $isSystemAdmin = $actor->hasRole(UserType::SYSTEM_ADMIN->value);

        $payment = Payment::with('subscription')->findOrFail($data['id']);

        if (! $isSystemAdmin && $payment->user_id !== $actor->id) {
            throw new ApiException('Você só pode visualizar seus próprios pagamentos.', 403);
        }

        return new PaymentResource($payment);
    }

    /**
     * Verifica a autenticidade do webhook recebido.
     *
     * Confirmado em teste real de sandbox (2026-07-23): a Abacate Pay manda o
     * secret em texto puro no header `X-Webhook-Secret` (também presente em
     * `payload.webhookSecret`), não uma assinatura HMAC. Existe também um header
     * `X-Webhook-Signature`, mas não foi possível confirmar o algoritmo exato
     * de cálculo a partir do payload capturado — não implementado por ora.
     *
     * ATENÇÃO: o teste foi feito com `devMode: true`. Vale confirmar que o
     * header `X-Webhook-Secret` também é enviado em produção antes de contar
     * apenas com ele.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = config('services.abacate_pay.webhook_secret');
        $received = $request->header('X-Webhook-Secret');

        if (! $secret || ! $received) {
            return false;
        }

        return hash_equals((string) $secret, (string) $received);
    }

    public function handleWebhookEvent(string $event, array $payload): void
    {
        // Confirmado em teste real de sandbox: o id da transação vem em
        // data.transparent.id (não data.id) para eventos transparent.*.
        $externalId = $payload['data']['transparent']['id'] ?? null;

        if (! $externalId) {
            return;
        }

        $alreadyProcessed = PaymentWebhookEvent::query()
            ->where('event', $event)
            ->where('external_id', $externalId)
            ->whereNotNull('processed_at')
            ->exists();

        if ($alreadyProcessed) {
            return;
        }

        PaymentWebhookEvent::updateOrCreate(
            ['event' => $event, 'external_id' => $externalId],
            ['payload' => $payload],
        );

        if ($event === 'transparent.completed') {
            $this->confirmPixPayment($externalId);
        }

        PaymentWebhookEvent::query()
            ->where('event', $event)
            ->where('external_id', $externalId)
            ->update(['processed_at' => now()]);
    }

    private function confirmPixPayment(string $externalId): void
    {
        DB::transaction(function () use ($externalId): void {
            $payment = Payment::query()
                ->where('external_id', $externalId)
                ->lockForUpdate()
                ->first();

            if (! $payment || $payment->status === PaymentStatus::PAID) {
                return;
            }

            $payment->update(['status' => PaymentStatus::PAID, 'paid_at' => now()]);

            if ($payment->league_id === null) {
                $league = $this->leagueService->provisionLeagueForUser($payment->user, [
                    'name' => $payment->league_name,
                    'subscription_id' => $payment->subscription_id,
                    'subscription_duration' => $payment->months,
                    'owner' => ['full_name' => $payment->owner_full_name],
                ]);

                $payment->update(['league_id' => $league->id]);
            } else {
                $this->leagueService->renewSubscription([
                    'id' => $payment->league_id,
                    'subscription_id' => $payment->subscription_id,
                    'subscription_duration' => $payment->months,
                ]);

                $league = $payment->league;
            }

            SubscriptionPeriod::create([
                'league_id' => $league->id,
                'payment_id' => $payment->id,
                'subscription_id' => $payment->subscription_id,
                'months' => $payment->months,
                'starts_at' => $league->subscription_start,
                'ends_at' => $league->subscription_end,
                'status' => SubscriptionPeriodStatus::ACTIVE,
            ]);
        });
    }
}
