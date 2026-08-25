<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('league_subscription_id')->constrained('league_subscriptions')->restrictOnDelete();
            $table->foreignUuid('plan_id')->constrained('plans')->restrictOnDelete();
            $table->foreignUuid('plan_price_id')->constrained('plan_prices')->restrictOnDelete();
            $table->foreignUuid('payment_id')->unique()->constrained('payments')->restrictOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->char('currency', 3)->default('BRL');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_periods');
    }
};
