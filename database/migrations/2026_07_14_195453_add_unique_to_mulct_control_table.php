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
        Schema::table('mulct_control', function (Blueprint $table) {
            $table->unique(['league_id', 'user_id', 'season_id'], 'mulct_control_league_user_season_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mulct_control', function (Blueprint $table) {
            $table->dropUnique('mulct_control_league_user_season_unique');
        });
    }
};
