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
        Schema::create('squads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('league_id')->constrained('leagues');
            $table->foreignUuid('user_id')->constrained('users');
            $table->foreignUuid('player_id')->constrained('players');
            $table->foreignUuid('season_id')->constrained('seasons');
            $table->string('acquisition_type', 25);
            $table->timestamps();
            
            $table->unique(['player_id', 'season_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('squads');
    }
};
