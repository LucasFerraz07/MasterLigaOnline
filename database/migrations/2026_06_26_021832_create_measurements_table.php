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
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('vitalab_id')
                ->constrained('vitalabs')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('humidity', 5, 2)->nullable();
            $table->timestamp('measured_at')->nullable();
            $table->boolean('failed_measurement')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
