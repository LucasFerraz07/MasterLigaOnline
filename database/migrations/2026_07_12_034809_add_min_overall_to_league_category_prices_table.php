<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Piso de overall que já era usado manualmente antes desta migration.
     */
    private const DEFAULT_MIN_OVERALL = [
        'white' => 40,
        'bronze' => 60,
        'silver' => 70,
        'gold' => 80,
        'black' => 90,
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('league_category_prices', function (Blueprint $table) {
            $table->unsignedTinyInteger('min_overall')->nullable()->after('category');
        });

        foreach (self::DEFAULT_MIN_OVERALL as $category => $minOverall) {
            DB::table('league_category_prices')
                ->where('category', $category)
                ->update(['min_overall' => $minOverall]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('league_category_prices', function (Blueprint $table) {
            $table->dropColumn('min_overall');
        });
    }
};
