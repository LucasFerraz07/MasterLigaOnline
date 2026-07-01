<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name', 150);
            $table->string('cpf', 14)->unique();
            $table->foreignUuid('league_id')->unique()->constrained('league_id')->cascadeOnDelete();
            $table->foreignUuid('user_id')->unique()->constrained('user_id');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owners');
    }
};
