<?php

namespace Tests\Feature\Season;

use App\Enums\AcquisitionType;
use App\Enums\Category;
use App\Enums\LeaguePhase;
use App\Enums\SeasonStatus;
use App\Enums\UserType;
use App\Models\ClubIdentity;
use App\Models\League;
use App\Models\LeagueCategoryPrice;
use App\Models\Player;
use App\Models\Season;
use App\Models\Squad;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Season\SeasonService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SquadLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->unsignedTinyInteger('user_limit')->nullable();
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
        Schema::create('leagues', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->unsignedTinyInteger('silver_limit')->nullable();
            $table->unsignedTinyInteger('golden_limit')->nullable();
            $table->unsignedTinyInteger('black_limit')->nullable();
            $table->unsignedTinyInteger('mulct_contract_limit')->default(2);
            $table->unsignedTinyInteger('player_limit')->nullable();
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
    }

    protected function tearDown(): void
    {
        foreach (['squads', 'matches', 'league_category_prices', 'seasons', 'club_identities', 'players', 'users', 'leagues', 'subscriptions'] as $table) {
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

    /** @return array{League, User, Season} */
    private function createLeagueContext(array $limits = []): array
    {
        $subscription = Subscription::create([
            'name' => 'Plano de teste',
            'price' => '10.00',
        ]);
        $league = League::create([
            'name' => 'Liga de teste',
            'subscription_id' => $subscription->id,
            'subscription_start' => now(),
            'subscription_end' => now()->addYear(),
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
