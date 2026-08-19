<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $transactionType = DB::table('transaction_types')->where('name', 'player_release');

        if ($transactionType->exists()) {
            $transactionType->update([
                'description' => 'Dispensa de jogador',
                'operation' => 'debit',
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('transaction_types')->insert([
            'id' => (string) Str::uuid(),
            'name' => 'player_release',
            'description' => 'Dispensa de jogador',
            'operation' => 'debit',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('transaction_types')->where('name', 'player_release')->delete();
    }
};
