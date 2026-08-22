<?php

namespace App\Http\Controllers\Webhook;

use App\Builder\ReturnApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Player\ImportPlayersRequest;
use App\Services\Player\PlayerImportService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\JsonResponse;

#[Group(name: 'Webhook')]
class ImportPlayersWebhook extends Controller
{
    public function __construct(
        private readonly PlayerImportService $service,
    ) {}

    #[Endpoint(
        operationId: 'importPlayersWebhook',
        title: 'Import Players CSV',
        description: '**operationId:** `importPlayersWebhook` — Recebe um CSV de jogadores enviado pelo scraper e cria ou atualiza o catálogo global pelo nome do jogador. Rota exclusiva para integração servidor-servidor: não deve ser chamada pelo frontend. Envie o arquivo como `multipart/form-data` no campo `file` e informe o token secreto no header `X-Player-Import-Token`. Aceita arquivos CSV/TXT de até 10 MB. Em **200**, `data` informa os totais `created`, `updated` e `skipped`.'
    )]
    #[HeaderParameter(
        name: 'X-Player-Import-Token',
        description: 'Token secreto configurado em `PLAYER_IMPORT_TOKEN`. Obrigatório para autorizar a importação.',
        required: true,
        type: 'string',
        example: 'seu-token-secreto'
    )]
    public function handle(ImportPlayersRequest $request): JsonResponse
    {
        $expectedToken = (string) config('services.player_import.token');
        $providedToken = (string) $request->header('X-Player-Import-Token');

        if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            /**
             * @status 401
             *
             * @body array{error: true, message: string, data: null}
             */
            return ReturnApi::error('Token de importação inválido.', null, 401);
        }

        $result = $this->service->import($request->file('file')->path());

        /**
         * @status 200
         *
         * @body array{error: false, message: string, data: array{created: int, updated: int, skipped: int}}
         */
        return ReturnApi::success($result, 'Jogadores importados com sucesso.');
    }
}
