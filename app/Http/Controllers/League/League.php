<?php

namespace App\Http\Controllers\League;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\League\ShowLeagueRequest;
use App\Services\League\LeagueService;
use Illuminate\Http\JsonResponse;

class League extends Controller
{
    public function __construct(
        private readonly LeagueService $service
    ) {}

    public function show(ShowLeagueRequest $request): JsonResponse
    {
        try {
            $data = $this->service->show($request->validated());
            return ReturnApi::success($data, 'Liga encontrada com sucesso.');
        } catch (ApiException $e) {
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
