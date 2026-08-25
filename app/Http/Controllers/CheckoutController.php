<?php

namespace App\Http\Controllers;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Requests\Checkout\StoreCheckoutPaymentRequest;
use App\Http\Requests\Checkout\StoreCheckoutRequest;
use App\Http\Resources\CheckoutResource;
use App\Models\Checkout;
use App\Services\Checkout\CheckoutService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

#[Group(name: 'Checkout')]
class CheckoutController extends Controller
{
    public function __construct(private readonly CheckoutService $service) {}

    #[Endpoint(operationId: 'storeCheckout', title: 'Store Checkout', description: '**operationId:** `storeCheckout` — Cria um checkout para contratação inicial ou renovação da assinatura da liga. Requer o cabeçalho `Idempotency-Key`. Em **200**, `data` segue o schema **CheckoutResource**. Requer autenticação.')]
    public function store(StoreCheckoutRequest $request): JsonResponse
    {
        return $this->respond(fn () => new CheckoutResource($this->service->create(Auth::user(), $request->validated())), 'Checkout criado.');
    }

    #[Endpoint(operationId: 'showCheckout', title: 'Show Checkout', description: '**operationId:** `showCheckout` — Obtém um checkout do usuário autenticado; `system_admin` também pode consultá-lo. Em **200**, `data` segue o schema **CheckoutResource**. Requer autenticação.')]
    public function show(Checkout $checkout): JsonResponse
    {
        return $this->respond(fn () => new CheckoutResource($this->service->show(Auth::user(), $checkout)), 'Checkout encontrado.');
    }

    #[Endpoint(operationId: 'payCheckout', title: 'Pay Checkout', description: '**operationId:** `payCheckout` — Inicia o pagamento PIX de um checkout do usuário autenticado. Requer o cabeçalho `Idempotency-Key`; não aceita checkouts expirados, concluídos ou cancelados. Em **200**, `data` segue o schema **CheckoutResource**. Requer autenticação.')]
    public function pay(StoreCheckoutPaymentRequest $request, Checkout $checkout): JsonResponse
    {
        return $this->respond(fn () => new CheckoutResource($this->service->pay(Auth::user(), $checkout, $request->validated())), 'Pagamento iniciado.');
    }

    private function respond(callable $callback, string $message): JsonResponse
    {
        try {
            return ReturnApi::success($callback(), $message);
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
