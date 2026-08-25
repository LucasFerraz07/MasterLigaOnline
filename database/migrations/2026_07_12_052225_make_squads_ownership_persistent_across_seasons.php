<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('squads', function (Blueprint $table) {
            $table->dropIndex('squads_league_user_season_index');
            $table->dropUnique('squads_player_id_season_id_unique');
            $table->dropForeign(['season_id']);
            $table->dropColumn('season_id');
        });

        Schema::table('squads', function (Blueprint $table) {
            $table->unique(['player_id', 'league_id'], 'squads_player_id_league_id_unique');
            $table->index(['league_id', 'user_id'], 'squads_league_user_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('squads', function (Blueprint $table) {
            $table->dropIndex('squads_league_user_index');
            $table->dropUnique('squads_player_id_league_id_unique');
        });

        Schema::table('squads', function (Blueprint $table) {
            $table->foreignUuid('season_id')->constrained('seasons');
            $table->unique(['player_id', 'season_id'], 'squads_player_id_season_id_unique');
            $table->index(['league_id', 'user_id', 'season_id'], 'squads_league_user_season_index');
        });
    }
};
