<?php

namespace App\Http\Controllers\ClubIdentity;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClubIdentity\ChangeClubClubIdentityRequest;
use App\Http\Requests\ClubIdentity\IndexClubIdentityRequest;
use App\Http\Requests\ClubIdentity\ShowClubIdentityRequest;
use App\Services\ClubIdentity\ClubIdentityService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'ClubIdentity')]
class ClubIdentity extends Controller
{
    public function __construct(
        private readonly ClubIdentityService $service
    ) {}

    #[Endpoint(operationId: 'indexClubIdentity', title: 'Index Club Identity', description: '**operationId:** `indexClubIdentity` — Lista os clubes dos participantes da liga. Em **200**, `data` segue o schema **ClubIdentityCollection** (paginado). Requer permissão: club-identity.view')]
    public function index(IndexClubIdentityRequest $request): JsonResponse
    {
        try {
            $data = $this->service->index($request->validated());
            return ReturnApi::success($data, 'Clubes listados com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showClubIdentity', title: 'Show Club Identity', description: '**operationId:** `showClubIdentity` — Obtém o clube de um participante. Em **200**, `data` segue o schema **ClubIdentityResource**. Requer permissão: club-identity.view')]
    public function show(ShowClubIdentityRequest $request): JsonResponse
    {
        try {
            $data = $this->service->show($request->validated());
            return ReturnApi::success($data, 'Clube encontrado com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'changeClubClubIdentity', title: 'Change Club Identity', description: '**operationId:** `changeClubClubIdentity` — Troca o clube de um participante, restrito ao dono durante a Janela Inicial, para um clube ainda não usado na liga. Em **200**, `data` segue o schema **ClubIdentityResource**. Requer permissão: club-identity.update')]
    public function changeClub(ChangeClubClubIdentityRequest $request): JsonResponse
    {
        try {
            $data = $this->service->changeClub($request->validated());
            return ReturnApi::success($data, 'Clube alterado com sucesso.');
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
