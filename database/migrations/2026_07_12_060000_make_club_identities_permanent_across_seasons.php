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
        Schema::table('club_identities', function (Blueprint $table) {
            $table->dropUnique('uq_club_per_season_league');
            $table->dropUnique('uq_user_per_season');
            $table->dropForeign(['season_id']);
            $table->dropColumn('season_id');
        });

        Schema::table('club_identities', function (Blueprint $table) {
            $table->unique(['league_id', 'user_id'], 'uq_club_identities_user_per_league');
            $table->unique(['league_id', 'club_id'], 'uq_club_identities_club_per_league');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_identities', function (Blueprint $table) {
            $table->dropUnique('uq_club_identities_user_per_league');
            $table->dropUnique('uq_club_identities_club_per_league');
        });

        Schema::table('club_identities', function (Blueprint $table) {
            $table->foreignUuid('season_id')->constrained('seasons');
            $table->unique(['league_id', 'club_id', 'season_id'], 'uq_club_per_season_league');
            $table->unique(['user_id', 'season_id'], 'uq_user_per_season');
        });
    }
};
