<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('league_id')->nullable()->constrained('leagues')->nullOnDelete();
            $table->foreignUuid('plan_price_id')->constrained('plan_prices')->restrictOnDelete();
            $table->string('type');
            $table->string('status')->default('open');
            $table->string('league_name')->nullable();
            $table->string('owner_full_name', 150)->nullable();
            $table->unsignedBigInteger('amount_cents');
            $table->char('currency', 3)->default('BRL');
            $table->uuid('client_idempotency_key');
            $table->timestamp('expires_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'client_idempotency_key']);
        });
        Schema::create('league_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('league_id')->unique()->constrained('leagues')->restrictOnDelete();
            $table->foreignUuid('current_plan_id')->constrained('plans')->restrictOnDelete();
            $table->foreignUuid('current_plan_price_id')->constrained('plan_prices')->restrictOnDelete();
            $table->string('status')->default('active');
            $table->string('collection_mode')->default('manual');
            $table->string('gateway')->nullable();
            $table->string('external_subscription_id')->nullable()->unique();
            $table->timestamp('current_period_starts_at');
            $table->timestamp('current_period_ends_at');
            $table->timestamp('access_expires_at');
            $table->timestamp('next_billing_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_subscriptions');
        Schema::dropIfExists('checkouts');
    }
};
