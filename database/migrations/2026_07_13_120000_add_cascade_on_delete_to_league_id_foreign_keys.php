<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'seasons',
        'club_identities',
        'squads',
        'transfer_bids',
        'transfers',
        'financial_transactions',
        'matches',
        'standings',
        'mulct_control',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->dropForeign("{$table}_league_id_foreign");
                $blueprint->foreign('league_id')->references('id')->on('leagues')->cascadeOnDelete();
            });
        }

        Schema::table('users', function (Blueprint $blueprint): void {
            $blueprint->dropForeign('users_league_id_foreign');
            $blueprint->foreign('league_id')->references('id')->on('leagues')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->dropForeign("{$table}_league_id_foreign");
                $blueprint->foreign('league_id')->references('id')->on('leagues');
            });
        }

        Schema::table('users', function (Blueprint $blueprint): void {
            $blueprint->dropForeign('users_league_id_foreign');
            $blueprint->foreign('league_id')->references('id')->on('leagues')->nullOnDelete();
        });
    }
};
