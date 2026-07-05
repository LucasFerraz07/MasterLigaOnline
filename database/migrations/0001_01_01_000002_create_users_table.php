<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('username');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone', 15);
            $table->foreignUuid('league_id')->nullable()->constrained('leagues')->nullOnDelete();
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('user_type');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
