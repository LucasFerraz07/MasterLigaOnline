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
        Schema::create('mulct_control', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('league_id')->constrained('leagues');
            $table->foreignUuid('user_id')->constrained('users');
            $table->foreignUuid('season_id')->constrained('seasons');
            $table->unsignedInteger('buyouts_purchased')->default(0);
            $table->unsignedInteger('buyouts_lost')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mulct_control');
    }
};
