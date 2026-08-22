<?php

namespace Tests\Feature;

use App\Models\Player;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlayerImportWebhookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('players', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique();
            $table->unsignedTinyInteger('overall');
            $table->string('position', 20)->nullable();
            $table->string('nationality', 50)->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });

        config(['services.player_import.token' => 'test-import-token']);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('players');

        parent::tearDown();
    }

    public function test_it_imports_and_updates_players_from_a_csv_upload(): void
    {
        $firstFile = UploadedFile::fake()->createWithContent('players.csv', implode("\n", [
            'Posição,Nome,Nacionalidade,Overall',
            'CA,Jogador Novo,Brasil,88',
            'SA,Jogador Existente,Argentina,82',
        ]));

        $response = $this->withHeader('X-Player-Import-Token', 'test-import-token')
            ->post('/api/webhooks/players/import', ['file' => $firstFile]);

        $response
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.created', 2)
            ->assertJsonPath('data.updated', 0)
            ->assertJsonPath('data.skipped', 0);

        $secondFile = UploadedFile::fake()->createWithContent('players.csv', implode("\n", [
            'Posição,Nome,Nacionalidade,Overall',
            'MAT,Jogador Existente,Argentina,91',
        ]));

        $this->withHeader('X-Player-Import-Token', 'test-import-token')
            ->post('/api/webhooks/players/import', ['file' => $secondFile])
            ->assertOk()
            ->assertJsonPath('data.created', 0)
            ->assertJsonPath('data.updated', 1);

        $this->assertDatabaseHas('players', [
            'name' => 'Jogador Existente',
            'position' => 'MAT',
            'overall' => 91,
        ]);
        $this->assertSame(2, Player::count());
    }

    public function test_it_rejects_an_invalid_import_token(): void
    {
        $file = UploadedFile::fake()->createWithContent('players.csv', "Nome,Overall\nJogador,80");

        $this->withHeader('X-Player-Import-Token', 'invalid-token')
            ->post('/api/webhooks/players/import', ['file' => $file])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Token de importação inválido.');
    }
}
