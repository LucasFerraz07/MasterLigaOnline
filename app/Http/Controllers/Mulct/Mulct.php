<?php

namespace App\Http\Controllers\Mulct;

use App\Builder\ReturnApi;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mulct\BuyMulctRequest;
use App\Http\Requests\Mulct\ControlMulctRequest;
use App\Services\Mulct\MulctService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'Mulct')]
class Mulct extends Controller
{
    public function __construct(
        private readonly MulctService $service
    ) {}

    #[Endpoint(operationId: 'buyMulct', title: 'Buy Mulct', description: '**operationId:** `buyMulct` — Compra um jogador de outro participante por multa (sem aceite do vendedor), pagando o dobro do passe. Restrito à Primeira Janela; bloqueado se o jogador foi adquirido nesta temporada ou se comprador/vendedor já atingiram o limite de multas da liga. Em **200**, `data` segue o schema **SquadResource**. Requer permissão: mulct.create')]
    public function buy(BuyMulctRequest $request): JsonResponse
    {
        try {
            $data = $this->service->buy($request->validated());
            return ReturnApi::success($data, 'Jogador adquirido por multa com sucesso.');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ReturnApi::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'controlMulct', title: 'Mulct Control', description: '**operationId:** `controlMulct` — Retorna quantas compras e perdas por multa o usuário autenticado já usou na temporada atual, e o limite configurado pela liga. Em **200**, `data` segue o schema **MulctControlResource**. Requer permissão: mulct.view')]
    public function control(ControlMulctRequest $request): JsonResponse
    {
        try {
            $data = $this->service->control();
            return ReturnApi::success($data, 'Controle de multa obtido com sucesso.');
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
