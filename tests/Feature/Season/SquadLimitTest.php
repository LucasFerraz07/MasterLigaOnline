<?php

namespace Tests\Feature\Season;

use App\Enums\AcquisitionType;
use App\Enums\Category;
use App\Enums\LeaguePhase;
use App\Enums\SeasonStatus;
use App\Enums\UserType;
use App\Models\ClubIdentity;
use App\Models\FinancialTransaction;
use App\Models\Game;
use App\Models\League;
use App\Models\LeagueCategoryPrice;
use App\Models\Player;
use App\Models\Season;
use App\Models\Squad;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\League\LeagueService;
use App\Services\Season\SeasonService;
use App\Services\Squad\SquadService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SquadLimitTest extends TestCase
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
        Schema::create('league_subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('league_id');
            $table->string('status');
            $table->timestamp('access_expires_at');
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
        Schema::create('players', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique();
            $table->unsignedTinyInteger('overall');
            $table->string('position', 20)->nullable();
            $table->string('nationality', 50)->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
        Schema::create('club_identities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('league_id');
            $table->uuid('user_id');
            $table->uuid('club_id');
            $table->timestamps();
        });
        Schema::create('owners', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('full_name');
            $table->uuid('league_id');
            $table->uuid('user_id');
            $table->softDeletes();
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
        });
        Schema::create('league_category_prices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('league_id');
            $table->string('category', 20);
            $table->decimal('base_salary', 12, 2);
            $table->unsignedTinyInteger('min_overall');
            $table->timestamps();
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
        Schema::create('transfers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('league_id');
            $table->uuid('bid_id')->nullable();
            $table->uuid('player_id');
            $table->uuid('seller_id')->nullable();
            $table->uuid('buyer_id')->nullable();
            $table->uuid('season_id');
            $table->string('type', 20);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Auth::forgetUser();

        foreach (['transfers', 'notifications', 'financial_transactions', 'transaction_types', 'squads', 'matches', 'league_category_prices', 'seasons', 'owners', 'club_identities', 'players', 'model_has_roles', 'roles', 'league_subscriptions', 'users', 'leagues'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_it_does_not_cut_players_when_every_limit_is_null(): void
    {
        [$league, $user, $season] = $this->createLeagueContext();
        $this->createPlayerInSquad($league, $user, 'Black player', 99);
        $this->createPlayerInSquad($league, $user, 'Silver player', 82);

        app(SeasonService::class)->advancePhase(['id' => $season->id]);

        $this->assertSame(2, Squad::withoutGlobalScopes()->where('league_id', $league->id)->count());
        $this->assertDatabaseHas('seasons', [
            'id' => $season->id,
            'phase' => LeaguePhase::FirstHalf->value,
            'status' => SeasonStatus::Active->value,
        ]);
    }

    public function test_it_uses_default_and_configurable_performance_credit_values(): void
    {
        [$league] = $this->createLeagueContext();

        $this->assertDatabaseHas('leagues', [
            'id' => $league->id,
            'win_credit' => '55000.00',
            'draw_credit' => '17000.00',
            'loss_credit' => '3000.00',
        ]);

        app(LeagueService::class)->update([
            'id' => $league->id,
            'win_credit' => '100.50',
            'draw_credit' => '50.25',
            'loss_credit' => '10.00',
        ]);

        $this->assertDatabaseHas('leagues', [
            'id' => $league->id,
            'win_credit' => '100.50',
            'draw_credit' => '50.25',
            'loss_credit' => '10.00',
        ]);
    }

    public function test_it_releases_the_highest_overall_players_over_the_total_limit(): void
    {
        [$league, $user, $season] = $this->createLeagueContext(['player_limit' => 2]);
        $highest = $this->createPlayerInSquad($league, $user, 'Highest player', 99);
        $secondHighest = $this->createPlayerInSquad($league, $user, 'Second highest player', 95);
        $kept = $this->createPlayerInSquad($league, $user, 'Kept player', 82);

        app(SeasonService::class)->advancePhase(['id' => $season->id]);

        $this->assertDatabaseMissing('squads', ['player_id' => $highest->id, 'league_id' => $league->id]);
        $this->assertDatabaseHas('squads', ['player_id' => $secondHighest->id, 'league_id' => $league->id]);
        $this->assertDatabaseHas('squads', ['player_id' => $kept->id, 'league_id' => $league->id]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'player_released_by_league_limit',
            'title' => 'Jogador liberado do elenco',
            'body' => 'O jogador Highest player foi liberado do seu elenco por exceder o limite definido pela liga.',
        ]);
    }

    public function test_it_records_a_player_release_as_a_transfer_to_the_free_market(): void
    {
        [$league, $user, $season] = $this->createLeagueContext();
        $player = $this->createPlayerInSquad($league, $user, 'Released player', 80);
        $squad = Squad::withoutGlobalScopes()
            ->where('league_id', $league->id)
            ->where('player_id', $player->id)
            ->firstOrFail();

        $user->update(['balance' => '1000.00']);
        TransactionType::create([
            'name' => 'player_release',
            'description' => 'Dispensa de jogador',
            'operation' => 'debit',
        ]);
        Auth::setUser($user);

        app(SquadService::class)->releasePlayer(['id' => $squad->id]);

        $this->assertDatabaseMissing('squads', ['id' => $squad->id]);
        $this->assertDatabaseHas('transfers', [
            'league_id' => $league->id,
            'player_id' => $player->id,
            'seller_id' => $user->id,
            'buyer_id' => null,
            'season_id' => $season->id,
            'type' => 'release',
            'amount' => '500.00',
        ]);
    }

    public function test_it_applies_category_limits_after_the_total_limit(): void
    {
        [$league, $user, $season] = $this->createLeagueContext([
            'player_limit' => 5,
            'black_limit' => 1,
            'silver_limit' => 1,
        ]);
        $overallLimitCut = $this->createPlayerInSquad($league, $user, 'Black 100', 100);
        $blackLimitCut = $this->createPlayerInSquad($league, $user, 'Black 99', 99);
        $silverLimitCut = $this->createPlayerInSquad($league, $user, 'Silver 84', 84);
        $keptBlack = $this->createPlayerInSquad($league, $user, 'Black 98', 98);
        $keptSilver = $this->createPlayerInSquad($league, $user, 'Silver 82', 82);
        $keptBronze = $this->createPlayerInSquad($league, $user, 'Bronze 75', 75);

        app(SeasonService::class)->advancePhase(['id' => $season->id]);

        $this->assertDatabaseMissing('squads', ['player_id' => $overallLimitCut->id, 'league_id' => $league->id]);
        $this->assertDatabaseMissing('squads', ['player_id' => $blackLimitCut->id, 'league_id' => $league->id]);
        $this->assertDatabaseMissing('squads', ['player_id' => $silverLimitCut->id, 'league_id' => $league->id]);
        $this->assertDatabaseHas('squads', ['player_id' => $keptBlack->id, 'league_id' => $league->id]);
        $this->assertDatabaseHas('squads', ['player_id' => $keptSilver->id, 'league_id' => $league->id]);
        $this->assertDatabaseHas('squads', ['player_id' => $keptBronze->id, 'league_id' => $league->id]);
    }

    public function test_it_debits_each_participants_total_squad_salary_when_the_season_ends(): void
    {
        [$league, $user, $season] = $this->createLeagueContext();
        $otherUser = User::withoutGlobalScopes()
            ->where('league_id', $league->id)
            ->where('id', '!=', $user->id)
            ->firstOrFail();

        $user->update(['balance' => '150.00']);
        $otherUser->update(['balance' => '250.00']);
        $this->createPlayerInSquad($league, $user, 'Salary player one', 80);
        $this->createPlayerInSquad($league, $user, 'Salary player two', 81);
        $this->createPlayerInSquad($league, $otherUser, 'Salary player three', 82);

        $season->update([
            'phase' => LeaguePhase::SecondHalf,
            'status' => SeasonStatus::Active,
        ]);

        app(SeasonService::class)->advancePhase(['id' => $season->id]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'balance' => '-50.00']);
        $this->assertDatabaseHas('users', ['id' => $otherUser->id, 'balance' => '150.00']);
        $this->assertDatabaseHas('seasons', [
            'id' => $season->id,
            'phase' => LeaguePhase::Ended->value,
            'status' => SeasonStatus::Closed->value,
        ]);
    }

    public function test_it_credits_consolidated_performance_before_debiting_squad_salaries(): void
    {
        [$league, $user, $season] = $this->createLeagueContext();
        $otherUser = User::withoutGlobalScopes()
            ->where('league_id', $league->id)
            ->where('id', '!=', $user->id)
            ->firstOrFail();
        $byeUser = User::create([
            'username' => 'participant-'.str()->uuid(),
            'email' => str()->uuid().'@example.test',
            'password' => 'password',
            'phone' => '11999999999',
            'league_id' => $league->id,
            'user_type' => UserType::USER,
        ]);

        $league->update([
            'win_credit' => '55000.00',
            'draw_credit' => '17000.00',
            'loss_credit' => '3000.00',
        ]);
        $user->update(['balance' => '0.00']);
        $otherUser->update(['balance' => '0.00']);
        $byeUser->update(['balance' => '0.00']);
        $this->createPlayerInSquad($league, $user, 'Performance salary player', 80);

        TransactionType::create([
            'name' => 'season_performance_credit',
            'description' => 'Crédito por desempenho na temporada',
            'operation' => 'credit',
        ]);
        $season->update([
            'phase' => LeaguePhase::SecondHalf,
            'status' => SeasonStatus::Active,
        ]);
        Game::create([
            'league_id' => $league->id,
            'season_id' => $season->id,
            'home_user_id' => $user->id,
            'away_user_id' => $otherUser->id,
            'home_goals' => 2,
            'away_goals' => 1,
            'round' => 1,
            'half' => 2,
            'is_bye' => false,
            'status' => 'finished',
        ]);
        Game::create([
            'league_id' => $league->id,
            'season_id' => $season->id,
            'home_user_id' => $user->id,
            'away_user_id' => $otherUser->id,
            'home_goals' => 0,
            'away_goals' => 0,
            'round' => 2,
            'half' => 2,
            'is_bye' => false,
            'status' => 'finished',
        ]);
        Game::create([
            'league_id' => $league->id,
            'season_id' => $season->id,
            'home_user_id' => $byeUser->id,
            'away_user_id' => null,
            'home_goals' => 0,
            'away_goals' => 0,
            'round' => 1,
            'half' => 2,
            'is_bye' => true,
            'status' => 'finished',
        ]);

        app(SeasonService::class)->advancePhase(['id' => $season->id]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'balance' => '71900.00']);
        $this->assertDatabaseHas('users', ['id' => $otherUser->id, 'balance' => '20000.00']);
        $this->assertDatabaseHas('users', ['id' => $byeUser->id, 'balance' => '55000.00']);
        $this->assertSame(3, FinancialTransaction::withoutGlobalScopes()
            ->where('league_id', $league->id)
            ->where('transaction_type_id', TransactionType::where('name', 'season_performance_credit')->firstOrFail()->id)
            ->count());
    }

    /** @return array{League, User, Season} */
    private function createLeagueContext(array $limits = []): array
    {
        $league = League::create([
            'name' => 'Liga de teste',
            ...$limits,
        ]);
        $user = User::create([
            'username' => 'participant-'.str()->uuid(),
            'email' => str()->uuid().'@example.test',
            'password' => 'password',
            'phone' => '11999999999',
            'league_id' => $league->id,
            'user_type' => UserType::USER,
        ]);
        $secondUser = User::create([
            'username' => 'participant-'.str()->uuid(),
            'email' => str()->uuid().'@example.test',
            'password' => 'password',
            'phone' => '11999999999',
            'league_id' => $league->id,
            'user_type' => UserType::USER,
        ]);
        $season = Season::create([
            'league_id' => $league->id,
            'season_number' => 1,
            'start_date' => now(),
            'status' => SeasonStatus::Open,
            'phase' => LeaguePhase::FirstWindow,
        ]);

        foreach (Category::cases() as $category) {
            LeagueCategoryPrice::create([
                'league_id' => $league->id,
                'category' => $category,
                'base_salary' => '100.00',
                'min_overall' => $category->defaultMinOverall(),
            ]);
        }

        foreach ([$user, $secondUser] as $participant) {
            ClubIdentity::create([
                'league_id' => $league->id,
                'user_id' => $participant->id,
                'club_id' => str()->uuid(),
            ]);
        }

        return [$league, $user, $season];
    }

    private function createPlayerInSquad(League $league, User $user, string $name, int $overall): Player
    {
        $player = Player::create([
            'name' => $name,
            'overall' => $overall,
        ]);

        Squad::create([
            'league_id' => $league->id,
            'user_id' => $user->id,
            'player_id' => $player->id,
            'acquisition_type' => AcquisitionType::Initial,
            'salary' => '100.00',
            'acquired_at' => now(),
        ]);

        return $player;
    }
}
