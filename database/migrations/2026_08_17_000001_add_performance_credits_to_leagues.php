<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table): void {
            $table->decimal('win_credit', 12, 2)->default(55000)->after('player_limit');
            $table->decimal('draw_credit', 12, 2)->default(17000)->after('win_credit');
            $table->decimal('loss_credit', 12, 2)->default(3000)->after('draw_credit');
        });

        $transactionType = DB::table('transaction_types')
            ->where('name', 'season_performance_credit');

        if ($transactionType->exists()) {
            $transactionType->update([
                'description' => 'Crédito por desempenho na temporada',
                'operation' => 'credit',
                'updated_at' => now(),
            ]);
        } else {
            DB::table('transaction_types')->insert([
                'id' => (string) Str::uuid(),
                'name' => 'season_performance_credit',
                'description' => 'Crédito por desempenho na temporada',
                'operation' => 'credit',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table): void {
            $table->dropColumn(['win_credit', 'draw_credit', 'loss_credit']);
        });
    }
};
