<?php

namespace App\Http\Controllers\Company;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\IndexCompanyRequest;
use App\Http\Requests\Company\StoreCompanyRequest;
use App\Services\Company\CompanyService;
use Illuminate\Http\JsonResponse;

class Company extends Controller
{
    public function __construct(
        private readonly CompanyService $service
    ) {}

    public function index(IndexCompanyRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Empresas listadas com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        try {
            $data = $this->service->store($request->validated());
            return ReturnApi::success($data, 'Empresa criada com sucesso.', 201);
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
