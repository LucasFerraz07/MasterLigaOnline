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
        Schema::create('transfers', function (Blueprint $table) {
            $table->uuid('id');
            $table->foreignUuid('league_id')->constrained('leagues');
            $table->foreignUuid('bid_id')->nullable()->constrained('transfer_bids'); // null para mulct e free
            $table->foreignUuid('player_id')->constrained('players');
            $table->foreignUuid('seller_id')->nullable()->constrained('users');
            $table->foreignUuid('buyer_id')->constrained('users');
            $table->foreignUuid('season_id')->constrained('seasons');
            $table->string('type', 20);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
