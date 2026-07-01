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
        Schema::create('seasons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('league_id')->constrained('leagues');
            $table->unsignedInteger('season_number');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('open');
            $table->string('phase', 30)->default('window_opening');
            $table->timestamps();
            $table->unique(['league_id', 'season_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
