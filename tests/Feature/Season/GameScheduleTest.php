<?php

namespace Tests\Feature\Season;

use App\Enums\LeaguePhase;
use App\Enums\MatchStatus;
use App\Enums\SeasonStatus;
use App\Enums\UserType;
use App\Exceptions\ApiException;
use App\Models\ClubIdentity;
use App\Models\Game;
use App\Models\League;
use App\Models\Season;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\Game\GameScheduleService;
use App\Services\Game\GameService;
use App\Services\Season\SeasonService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GameScheduleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('leagues', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->unsignedTinyInteger('silver_limit')->nullable();
            $table->unsignedTinyInteger('golden_limit')->nullable();
            $table->unsignedTinyInteger('black_limit')->nullable();
            $table->unsignedTinyInteger('mulct_contract_limit')->default(2);
            $table->unsignedTinyInteger('player_limit')->nullable();
            $table->decimal('win_credit', 12, 2)->default(55000);
            $table->decimal('draw_credit', 12, 2)->default(17000);
            $table->decimal('loss_credit', 12, 2)->default(3000);
            $table->uuid('subscription_id')->nullable();
            $table->date('subscription_start');
            $table->date('subscription_end');
            $table->timestamp('deactivated_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('username');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone', 15);
            $table->uuid('league_id')->nullable();
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('user_type');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->uuid('model_id');
        });
        Schema::create('club_identities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('league_id');
            $table->uuid('user_id');
            $table->uuid('club_id');
            $table->timestamps();
        });
        Schema::create('seasons', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('league_id');
            $table->unsignedInteger('season_number');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('open');
            $table->string('phase', 30)->default('window_opening');
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('type');
            $table->string('title');
            $table->string('body')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('matches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('league_id');
            $table->uuid('season_id');
            $table->uuid('home_user_id');
            $table->uuid('away_user_id')->nullable();
            $table->unsignedTinyInteger('home_goals')->default(0);
            $table->unsignedTinyInteger('away_goals')->default(0);
            $table->unsignedTinyInteger('round');
            $table->unsignedTinyInteger('half');
            $table->boolean('is_bye')->default(false);
            $table->date('match_date')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->unique(['season_id', 'half', 'home_user_id', 'away_user_id']);
        });
        Schema::create('squads', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('league_id');
            $table->uuid('user_id');
            $table->uuid('player_id');
            $table->string('acquisition_type', 25);
            $table->decimal('salary', 12, 2);
            $table->timestamp('acquired_at')->nullable();
            $table->timestamps();
        });
        Schema::create('transaction_types', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 50)->unique();
            $table->string('description', 100)->nullable();
            $table->enum('operation', ['credit', 'debit']);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('financial_transactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('league_id');
            $table->uuid('user_id');
            $table->uuid('transaction_type_id');
            $table->decimal('amount', 12, 2);
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        TransactionType::create([
            'name' => 'season_performance_credit',
            'description' => 'Crédito por desempenho na temporada',
            'operation' => 'credit',
        ]);
    }

    protected function tearDown(): void
    {
        Auth::forgetUser();

        foreach (['notifications', 'financial_transactions', 'transaction_types', 'squads', 'matches', 'seasons', 'club_identities', 'model_has_roles', 'roles', 'users', 'leagues'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_it_generates_a_double_round_robin_for_an_even_number_of_participants(): void
    {
        [$league, $season] = $this->createLeagueContext();
        $participants = $this->createParticipants($league, 4);

        app(SeasonService::class)->advancePhase(['id' => $season->id]);

        $firstHalfGames = $this->gamesFor($season, 1);
        $secondHalfGames = $this->gamesFor($season, 2);

        $this->assertCount(6, $firstHalfGames);
        $this->assertCount(6, $secondHalfGames);
        $this->assertSame(6, $firstHalfGames->where('is_bye', false)->map(fn (Game $game) => $this->pairKey($game))->unique()->count());

        foreach (range(1, 3) as $round) {
            $this->assertRoundContainsEveryParticipantOnce($firstHalfGames->where('round', $round), $participants);
            $this->assertRoundContainsEveryParticipantOnce($secondHalfGames->where('round', $round), $participants);
        }

        foreach ($firstHalfGames as $game) {
            $this->assertDatabaseHas('matches', [
                'season_id' => $season->id,
                'half' => 2,
                'round' => $game->round,
                'home_user_id' => $game->away_user_id,
                'away_user_id' => $game->home_user_id,
                'is_bye' => false,
            ]);
        }

        $this->assertDatabaseHas('seasons', [
            'id' => $season->id,
            'phase' => LeaguePhase::FirstHalf->value,
            'status' => SeasonStatus::Active->value,
        ]);
    }

    public function test_it_notifies_all_league_users_when_the_phase_changes(): void
    {
        [$league, $season] = $this->createLeagueContext();
        $participants = $this->createParticipants($league, 2);

        app(SeasonService::class)->advancePhase(['id' => $season->id]);

        foreach ($participants as $participant) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $participant->id,
                'type' => 'season_phase_changed',
                'title' => 'Fase da temporada alterada',
                'body' => 'A temporada 1 avançou para a fase: Primeiro turno.',
            ]);
        }
    }

    public function test_it_notifies_all_league_users_when_a_season_is_created(): void
    {
        $league = League::create([
            'name' => 'Liga de jogos',
            'subscription_start' => now(),
            'subscription_end' => now()->addYear(),
        ]);
        $participant = $this->createUser($league, UserType::USER);
        Auth::setUser($participant);

        app(SeasonService::class)->store(['league_id' => $league->id]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $participant->id,
            'type' => 'season_created',
            'title' => 'Nova temporada iniciada',
            'body' => 'A temporada 1 foi iniciada.',
        ]);
    }

    public function test_it_rotates_a_bye_for_every_participant_in_each_half_when_the_count_is_odd(): void
    {
        [$league, $season] = $this->createLeagueContext();
        $participants = $this->createParticipants($league, 3);

        app(SeasonService::class)->advancePhase(['id' => $season->id]);

        foreach ([1, 2] as $half) {
            $games = $this->gamesFor($season, $half);
            $byes = $games->where('is_bye', true);

            $this->assertCount(6, $games);
            $this->assertCount(3, $byes);
            $this->assertEqualsCanonicalizing($participants->pluck('id')->all(), $byes->pluck('home_user_id')->all());
            $this->assertTrue($byes->every(fn (Game $game) => $game->status === MatchStatus::Finished));

            foreach (range(1, 3) as $round) {
                $this->assertRoundContainsEveryParticipantOnce($games->where('round', $round), $participants);
            }
        }
    }

    public function test_it_requires_at_least_two_active_participants_with_clubs_to_start_the_first_half(): void
    {
        [$league, $season] = $this->createLeagueContext();
        $this->createParticipants($league, 1);

        try {
            app(SeasonService::class)->advancePhase(['id' => $season->id]);
            $this->fail('Era esperado bloquear o início do Primeiro Turno com apenas um participante.');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->getCode());
        }

        $this->assertSame(0, Game::withoutGlobalScopes()->where('season_id', $season->id)->count());
        $this->assertDatabaseHas('seasons', [
            'id' => $season->id,
            'phase' => LeaguePhase::FirstWindow->value,
            'status' => SeasonStatus::Open->value,
        ]);
    }

    public function test_it_does_not_generate_duplicate_games_when_the_scheduler_runs_again(): void
    {
        [$league, $season] = $this->createLeagueContext();
        $this->createParticipants($league, 2);

        app(SeasonService::class)->advancePhase(['id' => $season->id]);
        app(GameScheduleService::class)->generateForSeason($season, $league);

        $this->assertSame(2, Game::withoutGlobalScopes()->where('season_id', $season->id)->count());
    }

    public function test_it_cannot_advance_to_the_mid_window_with_pending_first_half_games(): void
    {
        [$league, $season] = $this->createLeagueContext();
        $this->createParticipants($league, 2);
        app(SeasonService::class)->advancePhase(['id' => $season->id]);

        try {
            app(SeasonService::class)->advancePhase(['id' => $season->id]);
            $this->fail('Era esperado bloquear a Janela Intermediária com partidas pendentes no Primeiro Turno.');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->getCode());
        }

        $this->assertDatabaseHas('seasons', [
            'id' => $season->id,
            'phase' => LeaguePhase::FirstHalf->value,
            'status' => SeasonStatus::Active->value,
        ]);

        Game::withoutGlobalScopes()
            ->where('season_id', $season->id)
            ->where('half', 1)
            ->update(['status' => MatchStatus::Finished->value]);

        app(SeasonService::class)->advancePhase(['id' => $season->id]);

        $this->assertDatabaseHas('seasons', [
            'id' => $season->id,
            'phase' => LeaguePhase::MidWindow->value,
            'status' => SeasonStatus::Open->value,
        ]);
    }

    public function test_it_cannot_end_the_season_with_pending_second_half_games(): void
    {
        [$league, $season] = $this->createLeagueContext();
        $this->createParticipants($league, 2);
        app(SeasonService::class)->advancePhase(['id' => $season->id]);

        Game::withoutGlobalScopes()
            ->where('season_id', $season->id)
            ->where('half', 1)
            ->update(['status' => MatchStatus::Finished->value]);

        app(SeasonService::class)->advancePhase(['id' => $season->id]);
        app(SeasonService::class)->advancePhase(['id' => $season->id]);

        try {
            app(SeasonService::class)->advancePhase(['id' => $season->id]);
            $this->fail('Era esperado bloquear o encerramento com partidas pendentes no Segundo Turno.');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->getCode());
        }

        $this->assertDatabaseHas('seasons', [
            'id' => $season->id,
            'phase' => LeaguePhase::SecondHalf->value,
            'status' => SeasonStatus::Active->value,
        ]);

        Game::withoutGlobalScopes()
            ->where('season_id', $season->id)
            ->where('half', 2)
            ->update(['status' => MatchStatus::Finished->value]);

        app(SeasonService::class)->advancePhase(['id' => $season->id]);

        $this->assertDatabaseHas('seasons', [
            'id' => $season->id,
            'phase' => LeaguePhase::Ended->value,
            'status' => SeasonStatus::Closed->value,
        ]);
    }

    public function test_a_participant_can_publish_and_edit_a_result_during_the_corresponding_half(): void
    {
        [$league, $season] = $this->createLeagueContext();
        $participants = $this->createParticipants($league, 2);
        app(SeasonService::class)->advancePhase(['id' => $season->id]);
        $game = $this->gamesFor($season, 1)->firstOrFail();

        Auth::setUser($participants->first());
        app(GameService::class)->publishResult([
            'id' => $game->id,
            'home_goals' => 2,
            'away_goals' => 1,
        ]);

        app(GameService::class)->publishResult([
            'id' => $game->id,
            'home_goals' => 3,
            'away_goals' => 2,
        ]);

        $this->assertDatabaseHas('matches', [
            'id' => $game->id,
            'home_goals' => 3,
            'away_goals' => 2,
            'status' => MatchStatus::Finished->value,
        ]);
    }

    public function test_a_league_admin_can_publish_a_result_for_a_game_they_are_not_part_of(): void
    {
        [$league, $season] = $this->createLeagueContext();
        $participants = $this->createParticipants($league, 3);
        app(SeasonService::class)->advancePhase(['id' => $season->id]);

        $leagueAdmin = $participants->first();
        $leagueAdmin->update(['user_type' => UserType::LEAGUE_ADMIN]);
        $game = $this->gamesFor($season, 1)
            ->first(fn (Game $game) => ! $game->is_bye && ! in_array($leagueAdmin->id, [$game->home_user_id, $game->away_user_id], true));

        Auth::setUser($leagueAdmin);
        app(GameService::class)->publishResult([
            'id' => $game->id,
            'home_goals' => 1,
            'away_goals' => 0,
        ]);

        $this->assertDatabaseHas('matches', [
            'id' => $game->id,
            'status' => MatchStatus::Finished->value,
        ]);
    }

    public function test_a_user_who_is_not_involved_and_is_not_league_admin_cannot_publish_a_result(): void
    {
        [$league, $season] = $this->createLeagueContext();
        $this->createParticipants($league, 2);
        app(SeasonService::class)->advancePhase(['id' => $season->id]);
        $game = $this->gamesFor($season, 1)->firstOrFail();
        $outsider = $this->createUser($league, UserType::USER);

        Auth::setUser($outsider);

        try {
            app(GameService::class)->publishResult([
                'id' => $game->id,
                'home_goals' => 1,
                'away_goals' => 0,
            ]);
            $this->fail('Era esperado bloquear a publicação por alguém que não participa da partida.');
        } catch (ApiException $exception) {
            $this->assertSame(403, $exception->getCode());
        }
    }

    public function test_it_blocks_a_result_when_the_game_half_is_not_the_active_season_phase(): void
    {
        [$league, $season] = $this->createLeagueContext();
        $participants = $this->createParticipants($league, 2);
        app(SeasonService::class)->advancePhase(['id' => $season->id]);
        $secondHalfGame = $this->gamesFor($season, 2)->firstOrFail();

        Auth::setUser($participants->first());

        try {
            app(GameService::class)->publishResult([
                'id' => $secondHalfGame->id,
                'home_goals' => 1,
                'away_goals' => 0,
            ]);
            $this->fail('Era esperado bloquear resultado do Segundo Turno enquanto o Primeiro Turno está ativo.');
        } catch (ApiException $exception) {
            $this->assertSame(409, $exception->getCode());
        }
    }

    public function test_it_blocks_results_for_bye_games(): void
    {
        [$league, $season] = $this->createLeagueContext();
        $this->createParticipants($league, 3);
        app(SeasonService::class)->advancePhase(['id' => $season->id]);
        $byeGame = $this->gamesFor($season, 1)->firstWhere('is_bye', true);
        $participant = User::withoutGlobalScopes()->findOrFail($byeGame->home_user_id);

        Auth::setUser($participant);

        try {
            app(GameService::class)->publishResult([
                'id' => $byeGame->id,
                'home_goals' => 1,
                'away_goals' => 0,
            ]);
            $this->fail('Era esperado bloquear publicação de resultado para uma folga.');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->getCode());
        }
    }

    /** @return array{League, Season} */
    private function createLeagueContext(): array
    {
        $league = League::create([
            'name' => 'Liga de jogos',
            'subscription_start' => now(),
            'subscription_end' => now()->addYear(),
        ]);
        $season = Season::create([
            'league_id' => $league->id,
            'season_number' => 1,
            'start_date' => now(),
            'status' => SeasonStatus::Open,
            'phase' => LeaguePhase::FirstWindow,
        ]);

        return [$league, $season];
    }

    /** @return Collection<int, User> */
    private function createParticipants(League $league, int $count): Collection
    {
        return collect(range(1, $count))->map(function () use ($league): User {
            $user = $this->createUser($league, UserType::USER);

            ClubIdentity::create([
                'league_id' => $league->id,
                'user_id' => $user->id,
                'club_id' => str()->uuid(),
            ]);

            return $user;
        });
    }

    private function createUser(League $league, UserType $userType): User
    {
        return User::create([
            'username' => 'participant-'.str()->uuid(),
            'email' => str()->uuid().'@example.test',
            'password' => 'password',
            'phone' => '11999999999',
            'league_id' => $league->id,
            'user_type' => $userType,
        ]);
    }

    /** @return Collection<int, Game> */
    private function gamesFor(Season $season, int $half): Collection
    {
        return Game::withoutGlobalScopes()
            ->where('season_id', $season->id)
            ->where('half', $half)
            ->orderBy('round')
            ->get();
    }

    /** @param Collection<int, Game> $games @param Collection<int, User> $participants */
    private function assertRoundContainsEveryParticipantOnce(Collection $games, Collection $participants): void
    {
        $participantIds = $participants->pluck('id')->sort()->values()->all();
        $scheduledParticipantIds = $games
            ->flatMap(fn (Game $game) => array_filter([$game->home_user_id, $game->away_user_id]))
            ->sort()
            ->values()
            ->all();

        $this->assertSame($participantIds, $scheduledParticipantIds);
    }

    private function pairKey(Game $game): string
    {
        return collect([$game->home_user_id, $game->away_user_id])->sort()->implode(':');
    }
}
