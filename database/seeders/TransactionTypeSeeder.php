<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransactionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('transaction_types')->insert([
            [
                'name' => 'free_purchase',
                'description' => 'Compra gratuita',
                'operation' => 'debit',
            ],
            [
                'name' => 'negotiation_purchase',
                'description' => 'Compra por negociação',
                'operation' => 'debit'
            ],
            [
                'name' => 'mulct_purchase',
                'description' => 'Compra por arremate',
                'operation' => 'debit'
            ],
            [
                'name' => 'negotiation_sale',
                'description' => 'Venda por negociação',
                'operation' => 'credit'
            ],
            [
                'name' => 'mulct_sale',
                'description' => 'Venda por arremate',
                'operation' => 'credit'
            ],
            [
                'name' => 'initial_credit',
                'description' => 'Crédito inicial',
                'operation' => 'credit'
            ],
        ]);
    }
}
