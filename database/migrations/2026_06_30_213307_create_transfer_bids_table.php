<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('transfer_bids', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('league_id')->constrained();
            $table->foreignUuid('season_id')->constrained();
            $table->foreignUuid('proposer_id')->constrained('users');
            $table->foreignUuid('receiver_id')->constrained('users');
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE transfer_bids ADD CONSTRAINT chk_transfer_bid_different_users CHECK (proposer_id <> receiver_id)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_bids');
    }
};
