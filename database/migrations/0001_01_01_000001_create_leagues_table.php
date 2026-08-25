<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leagues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->unsignedTinyInteger('silver_limit')->nullable();
            $table->unsignedTinyInteger('golden_limit')->nullable();
            $table->unsignedTinyInteger('black_limit')->nullable();
            $table->unsignedTinyInteger('mulct_contract_limit')->default(2);
            $table->unsignedTinyInteger('player_limit')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leagues');
    }
};
