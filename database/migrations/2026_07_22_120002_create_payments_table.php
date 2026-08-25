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
            $table->foreignUuid('checkout_id')->constrained('checkouts')->restrictOnDelete();
            $table->string('gateway')->default('mercado_pago');
            $table->string('method')->default('pix');
            $table->unsignedBigInteger('amount_cents');
            $table->char('currency', 3)->default('BRL');
            $table->string('status')->default('creating');
            $table->string('status_detail')->nullable();
            $table->uuid('client_idempotency_key');
            $table->uuid('gateway_idempotency_key')->unique();
            $table->string('external_id')->nullable()->unique();
            $table->longText('pix_qr_code_base64')->nullable();
            $table->text('pix_copy_paste_code')->nullable();
            $table->text('pix_ticket_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            $table->unique(['checkout_id', 'client_idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
