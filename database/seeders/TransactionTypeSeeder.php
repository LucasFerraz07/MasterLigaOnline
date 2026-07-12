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
        ];

        foreach ($types as $type) {
            TransactionType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}