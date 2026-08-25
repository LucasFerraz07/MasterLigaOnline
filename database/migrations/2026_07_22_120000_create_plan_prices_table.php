<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('plan_id')->constrained('plans')->restrictOnDelete();
            $table->string('code')->unique();
            $table->unsignedSmallInteger('version')->default(1);
            $table->unsignedTinyInteger('interval_months');
            $table->unsignedBigInteger('amount_cents');
            $table->char('currency', 3)->default('BRL');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['plan_id', 'interval_months', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_prices');
    }
};
