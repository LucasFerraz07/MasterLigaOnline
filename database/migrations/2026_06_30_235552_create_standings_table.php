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
        Schema::create('standings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('league_id')->constrained('leagues');
            $table->foreignUuid('user_id')->constrained('users');
            $table->foreignUuid('season_id')->constrained('seasons');
            $table->unsignedTinyInteger('points')->default(0);
            $table->unsignedTinyInteger('wins')->default(0);
            $table->unsignedTinyInteger('draws')->default(0);
            $table->unsignedTinyInteger('losses')->default(0);
            $table->unsignedSmallInteger('goals_for')->default(0);
            $table->unsignedSmallInteger('goals_against')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'season_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standings');
    }
};
