<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('squads', function (Blueprint $table) {
            $table->timestamp('acquired_at')->nullable()->after('salary');
        });

        DB::table('squads')->whereNull('acquired_at')->update(['acquired_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('squads', function (Blueprint $table) {
            $table->dropColumn('acquired_at');
        });
    }
};