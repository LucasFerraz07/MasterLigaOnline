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
        Schema::create('transfer_bid_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('bid_id')->constrained('transfer_bids')->cascadeOnDelete();
            $table->string('side', 20);
            $table->string('item_type', 20);
            $table->foreignUuid('player_id')->nullable()->constrained('players');
            $table->decimal('amount', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_bid_items');
    }
};
