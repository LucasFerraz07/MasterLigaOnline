<?php

namespace App\Console\Commands;

use App\Services\Player\PlayerImportService;
use Illuminate\Console\Command;

class ImportPlayersFromCsv extends Command
{
    protected $signature = 'players:import {path=storage/app/imports/players.csv : Caminho do CSV gerado pelo script Python}';

    protected $description = 'Importa/atualiza jogadores a partir do CSV extraído da base do eFootball';

    public function handle(PlayerImportService $service): int
    {
        $path = $this->argument('path');
        $path = str_starts_with($path, '/') ? $path : base_path($path);

        try {
            $result = $service->import($path);
        } catch (\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Importação concluída: {$result['created']} criados, {$result['updated']} atualizados, {$result['skipped']} ignorados.");

        return self::SUCCESS;
    }
}
