<?php

namespace App\Http\Controllers\Measurement;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Measurement\IndexMeasurementRequest;
use App\Http\Requests\Measurement\StoreMeasurementRequest;
use App\Services\Measurement\Measurement as MeasurementService;
use Illuminate\Http\JsonResponse;

class Measurement extends Controller
{
    public function __construct(
        private readonly MeasurementService $service
    ) {}

    public function index(IndexMeasurementRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Medições listadas com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    public function store(StoreMeasurementRequest $request): JsonResponse
    {
        try {
            $data = $this->service->store($request->validated());
            return ReturnApi::success($data, 'Medição registrada com sucesso.', 201);
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
