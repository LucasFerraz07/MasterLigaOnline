<?php

namespace App\Http\Controllers;

use App\Enums\WebhookEventStatus;
use App\Jobs\ProcessMercadoPagoWebhook;
use App\Models\PaymentWebhookEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MercadoPago\Exceptions\InvalidWebhookSignatureException;
use MercadoPago\Webhook\WebhookSignatureValidator;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $resourceId = (string) ($request->query->get('data.id') ?? $request->query->get('data_id') ?? $request->input('data.id', ''));
        $requestId = (string) $request->header('x-request-id', '');
        try {
            WebhookSignatureValidator::validate($request->header('x-signature'), $requestId, $resourceId, (string) config('services.mercado_pago.webhook_secret'), 300);
        } catch (InvalidWebhookSignatureException|\InvalidArgumentException) {
            Log::warning('mercado_pago.webhook.invalid_signature', ['request_id' => $requestId]);

            return response()->json(['message' => 'Assinatura inválida.'], 401);
        }
        if ($resourceId === '' || $request->input('type') !== 'payment') {
            return response()->json(null, 200);
        }
        $payload = $request->all();
        $hash = hash('sha256', $request->getContent() ?: json_encode($payload));
        $providerEventId = (string) ($request->input('id') ?: $requestId ?: $hash);
        $event = PaymentWebhookEvent::query()
            ->where('provider', 'mercado_pago')
            ->where(fn ($query) => $query
                ->where('provider_event_id', $providerEventId)
                ->orWhere('payload_hash', $hash))
            ->first();

        if (! $event) {
            try {
                $event = PaymentWebhookEvent::create([
                    'provider' => 'mercado_pago',
                    'provider_event_id' => $providerEventId,
                    'resource_type' => 'payment',
                    'resource_id' => $resourceId,
                    'request_id' => $requestId ?: null,
                    'payload_hash' => $hash,
                    'payload' => $payload,
                    'status' => WebhookEventStatus::PENDING,
                ]);
            } catch (QueryException) {
                // Outra requisição concorrente venceu a inserção da mesma notificação.
                $event = PaymentWebhookEvent::query()
                    ->where('provider', 'mercado_pago')
                    ->where(fn ($query) => $query
                        ->where('provider_event_id', $providerEventId)
                        ->orWhere('payload_hash', $hash))
                    ->firstOrFail();
            }
        }
        if (! $event->processed_at) {
            ProcessMercadoPagoWebhook::dispatch($event->id);
        }

        return response()->json(null, 200);
    }
}
