<?php

namespace App\Services\Player;

use App\Models\Player;
use InvalidArgumentException;
use SplFileObject;

class PlayerImportService
{
    private const HEADER_MAP = [
        'posição' => 'position',
        'nome' => 'name',
        'nacionalidade' => 'nationality',
        'overall' => 'overall',
    ];

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function import(string $path): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException("Arquivo não encontrado: {$path}");
        }

        $file = new SplFileObject($path, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);
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

            if (count($header) !== count($row)) {
                $skipped++;

                continue;
            }

            $data = array_combine($header, $row);
            $name = trim((string) ($data['name'] ?? ''));

            if ($name === '') {
                $skipped++;

                continue;
            }

            $player = Player::updateOrCreate(
                ['name' => $name],
                [
                    'overall' => (int) ($data['overall'] ?? 0),
                    'position' => trim((string) ($data['position'] ?? '')) ?: null,
                    'nationality' => trim((string) ($data['nationality'] ?? '')) ?: null,
                ]
            );

            $player->wasRecentlyCreated ? $created++ : $updated++;
        }

        return compact('created', 'updated', 'skipped');
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
        $handle = fopen($path, 'r');
        $firstLine = $handle === false ? '' : (string) fgets($handle);

        if ($handle !== false) {
            fclose($handle);
        }

        return substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    }
}
