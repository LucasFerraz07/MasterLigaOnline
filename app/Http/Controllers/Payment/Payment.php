<?php

namespace App\Http\Controllers\Payment;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Services\Payment\PaymentService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'Payment')]
class Payment extends Controller
{
    public function __construct(
        private readonly PaymentService $service
    ) {}

    #[Endpoint(operationId: 'storePayment', title: 'Store Payment', description: '**operationId:** `storePayment` — Gera uma cobrança Pix para contratar um plano. Em **200**, `data` segue o schema **PaymentResource** (contém QR Code e código copia-e-cola). Requer apenas autenticação.')]
    public function store(StorePaymentRequest $request): JsonResponse
    {
        try {
            $data = $this->service->store($request->validated());

            /**
             * @status 200
             *
             * @body array{error: false, message: string, data: PaymentResource}
             */
            return ReturnApi::success($data, 'Cobrança Pix gerada com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
