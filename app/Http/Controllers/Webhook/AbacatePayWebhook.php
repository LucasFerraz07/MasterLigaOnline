<?php

namespace App\Http\Controllers\Webhook;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

#[Group(name: 'Webhook')]
class AbacatePayWebhook extends Controller
{
    public function __construct(
        private readonly PaymentService $service
    ) {}

    public function handle(Request $request): JsonResponse
    {
        // Log bruto proposital: viabiliza inspecionar em sandbox o payload/headers
        // reais antes de travar a verificação de assinatura definitiva (ver plano).
        Log::info('abacate_pay.webhook.received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        if (! $this->service->verifyWebhookSignature($request)) {
            return ReturnApi::error('Assinatura do webhook inválida.', null, 401);
        }

        try {
            $this->service->handleWebhookEvent((string) $request->input('event'), $request->all());

            return ReturnApi::success(null, 'Webhook processado com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
