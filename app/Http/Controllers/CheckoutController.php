<?php

namespace App\Http\Controllers;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Requests\Checkout\StoreCheckoutPaymentRequest;
use App\Http\Requests\Checkout\StoreCheckoutRequest;
use App\Http\Resources\CheckoutResource;
use App\Models\Checkout;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function __construct(private readonly CheckoutService $service) {}

    public function store(StoreCheckoutRequest $request): JsonResponse
    {
        return $this->respond(fn () => new CheckoutResource($this->service->create(Auth::user(), $request->validated())), 'Checkout criado.');
    }

    public function show(Checkout $checkout): JsonResponse
    {
        return $this->respond(fn () => new CheckoutResource($this->service->show(Auth::user(), $checkout)), 'Checkout encontrado.');
    }

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
