<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('subscription_id')->constrained('subscriptions');
            $table->foreignUuid('league_id')->nullable()->constrained('leagues')->cascadeOnDelete();
            $table->unsignedTinyInteger('months');
            $table->string('league_name');
            $table->string('owner_full_name', 150);
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending');
            $table->string('gateway')->default('abacate_pay');
            $table->string('external_id')->nullable()->unique();
            $table->text('pix_qr_code')->nullable();
            $table->text('pix_br_code')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
