<?php

namespace App\Http\Controllers\Webhook;

use App\Builder\ReturnApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Player\ImportPlayersRequest;
use App\Services\Player\PlayerImportService;
use Illuminate\Http\JsonResponse;

class ImportPlayersWebhook extends Controller
{
    public function __construct(
        private readonly PlayerImportService $service,
    ) {}

    public function handle(ImportPlayersRequest $request): JsonResponse
    {
        $expectedToken = (string) config('services.player_import.token');
        $providedToken = (string) $request->header('X-Player-Import-Token');

        if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return ReturnApi::error('Token de importação inválido.', null, 401);
        }

        $result = $this->service->import($request->file('file')->path());

        return ReturnApi::success($result, 'Jogadores importados com sucesso.');
    }
}
