<?php

namespace Tests\Feature\Club;

use App\Exceptions\ApiException;
use App\Models\Club;
use App\Models\ClubIdentity;
use App\Models\League;
use App\Models\User;
use App\Services\Club\ClubService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClubDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_does_not_remove_a_club_assigned_to_a_participant(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('images/clubs/crest.png', 'crest');

        $league = League::create(['name' => 'Liga teste']);
        $user = User::create([
            'username' => 'participant',
            'email' => 'participant@example.test',
            'password' => 'password',
            'phone' => '11999999999',
            'league_id' => $league->id,
            'user_type' => 'user',
        ]);
        $club = Club::create([
            'name' => 'Clube em uso',
            'crest' => 'images/clubs/crest.png',
            'region' => 'national',
        ]);

        ClubIdentity::create([
            'league_id' => $league->id,
            'user_id' => $user->id,
            'club_id' => $club->id,
        ]);

        try {
            app(ClubService::class)->destroy(['id' => $club->id]);
            $this->fail('Era esperada uma ApiException.');
        } catch (ApiException $exception) {
            $this->assertSame(409, $exception->getCode());
            $this->assertSame(
                'Não é possível excluir um clube atribuído a um participante.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseHas('clubs', ['id' => $club->id]);
        Storage::disk('public')->assertExists('images/clubs/crest.png');
    }

    public function test_it_removes_an_unassigned_club_and_its_crest(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('images/clubs/crest.png', 'crest');

        $club = Club::create([
            'name' => 'Clube livre',
            'crest' => 'images/clubs/crest.png',
            'region' => 'national',
        ]);

        app(ClubService::class)->destroy(['id' => $club->id]);

        $this->assertDatabaseMissing('clubs', ['id' => $club->id]);
        Storage::disk('public')->assertMissing('images/clubs/crest.png');
    }
}
