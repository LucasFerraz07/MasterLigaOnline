<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('league_id')->constrained('leagues');
            $table->foreignUuid('season_id')->constrained('seasons');
            $table->foreignUuid('home_user_id')->constrained('users');
            $table->foreignUuid('away_user_id')->nullable()->constrained('users');
            $table->unsignedTinyInteger('home_goals')->default(0);
            $table->unsignedTinyInteger('away_goals')->default(0);
            $table->unsignedTinyInteger('round');
            $table->unsignedTinyInteger('half'); // 1 = first half, 2 = second half
            $table->boolean('is_bye')->default(false); // true = rodada de folga
            $table->date('match_date')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->unique(['season_id', 'half', 'home_user_id', 'away_user_id'], 'uq_unique_match');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE matches ADD CONSTRAINT chk_diff_teams CHECK (away_user_id IS NULL OR home_user_id <> away_user_id)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
