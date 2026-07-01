<?php

namespace App\Console\Commands;

use App\Enums\Category;
use App\Models\Player;
use Illuminate\Console\Command;

class ImportPlayersFromCsv extends Command
{
    protected $signature = 'players:import {path=storage/app/imports/players.csv : Caminho do CSV gerado pelo script Python}';

    protected $description = 'Importa/atualiza jogadores a partir do CSV extraído da base do eFootball';

    private const HEADER_MAP = [
        'posição' => 'position',
        'nome' => 'name',
        'nacionalidade' => 'nationality',
        'overall' => 'overall',
        'categoria' => 'category',
    ];

    public function handle(): int
    {
        $path = $this->argument('path');
        $path = str_starts_with($path, '/') ? $path : base_path($path);

        if (! is_file($path)) {
            $this->error("Arquivo não encontrado: {$path}");

            return self::FAILURE;
        }

        $file = new \SplFileObject($path, 'r');
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD);
        $file->setCsvControl($this->detectDelimiter($path));

        $header = null;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($file as $row) {
            if ($row === null || $row === [null]) {
                continue;
            }

            if ($header === null) {
                $header = $this->mapHeader($row);

                continue;
            }

            $data = array_combine($header, $row);
            $name = trim((string) ($data['name'] ?? ''));
            $category = Category::tryFrom(strtolower(trim((string) ($data['category'] ?? ''))));

            if ($name === '' || $category === null) {
                $skipped++;
                $this->warn("Linha ignorada (nome ou categoria inválidos): ".implode(',', $row));

                continue;
            }

            $player = Player::updateOrCreate(
                ['name' => $name],
                [
                    'overall' => (int) ($data['overall'] ?? 0),
                    'position' => trim((string) ($data['position'] ?? '')) ?: null,
                    'nationality' => trim((string) ($data['nationality'] ?? '')) ?: null,
                    'category' => $category,
                ]
            );

            $player->wasRecentlyCreated ? $created++ : $updated++;
        }

        $this->info("Importação concluída: {$created} criados, {$updated} atualizados, {$skipped} ignorados.");

        return self::SUCCESS;
    }

    private function mapHeader(array $row): array
    {
        return array_map(
            fn (string $column) => self::HEADER_MAP[mb_strtolower(trim($column))] ?? mb_strtolower(trim($column)),
            $row
        );
    }

    private function detectDelimiter(string $path): string
    {
        $firstLine = fgets(fopen($path, 'r'));

        return substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    }
}