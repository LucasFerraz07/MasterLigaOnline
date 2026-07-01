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
        Schema::create('club_identities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('league_id')->constrained('leagues');
            $table->foreignUuid('user_id')->constrained('users');
            $table->foreignUuid('season_id')->constrained('seasons');
            $table->foreignUuid('club_id')->constrained('clubs');
            $table->timestamps();
             $table->unique(['league_id', 'club_id', 'season_id'], 'uq_club_per_season_league');
             $table->unique(['user_id', 'season_id'], 'uq_user_per_season');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_identities');
    }
};
