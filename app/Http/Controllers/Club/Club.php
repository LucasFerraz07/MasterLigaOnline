<?php

namespace App\Http\Controllers\Club;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexClubRequest;
use App\Services\Club\ClubService;
use Illuminate\Http\JsonResponse;

class Club extends Controller
{
    public function __construct(
        private readonly ClubService $service
    ) {}

    public function index(IndexClubRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Clubes listados com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
