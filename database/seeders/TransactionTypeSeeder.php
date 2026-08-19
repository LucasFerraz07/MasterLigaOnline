<?php

namespace Database\Seeders;

use App\Models\TransactionType;
use Illuminate\Database\Seeder;

class TransactionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'free_purchase',
                'description' => 'Compra gratuita',
                'operation' => 'debit',
            ],
            [
                'name' => 'player_release',
                'description' => 'Dispensa de jogador',
                'operation' => 'debit',
            ],
            [
                'name' => 'negotiation_purchase',
                'description' => 'Compra por negociação',
                'operation' => 'debit',
            ],
            [
                'name' => 'mulct_purchase',
                'description' => 'Compra por arremate',
                'operation' => 'debit',
            ],
            [
                'name' => 'negotiation_sale',
                'description' => 'Venda por negociação',
                'operation' => 'credit',
            ],
            [
                'name' => 'mulct_sale',
                'description' => 'Venda por arremate',
                'operation' => 'credit',
            ],
            [
                'name' => 'initial_credit',
                'description' => 'Crédito inicial',
                'operation' => 'credit',
            ],
            [
                'name' => 'manual_credit',
                'description' => 'Crédito manual (ajuste do dono da liga)',
                'operation' => 'credit',
            ],
            [
                'name' => 'manual_debit',
                'description' => 'Débito manual (ajuste do dono da liga)',
                'operation' => 'debit',
            ],
            [
                'name' => 'season_performance_credit',
                'description' => 'Crédito por desempenho na temporada',
                'operation' => 'credit',
            ],
        ];

        foreach ($types as $type) {
            TransactionType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
