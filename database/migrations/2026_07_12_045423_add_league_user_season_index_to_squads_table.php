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
            $table->index(['league_id', 'user_id', 'season_id'], 'squads_league_user_season_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('squads', function (Blueprint $table) {
            $table->dropIndex('squads_league_user_season_index');
        });
    }
};
