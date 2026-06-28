<?php

namespace App\Http\Controllers\Vitalab;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vitalab\IndexVitalabRequest;
use App\Http\Requests\Vitalab\StoreVitalabRequest;
use App\Services\Vitalab\VitalabService;
use Illuminate\Http\JsonResponse;

class Vitalab extends Controller
{
    public function __construct(
        private readonly VitalabService $service
    ) {}

    public function index(IndexVitalabRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Vitalabs listados com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    public function store(StoreVitalabRequest $request): JsonResponse
    {
        try {
            $data = $this->service->store($request->validated());
            return ReturnApi::success($data, 'Vitalab criado com sucesso.', 201);
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
