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
                'name_label' => 'Compra gratuita',
                'operation' => 'debit',
            ],
            [
                'name' => 'negotiation_purchase',
                'name_label' => 'Compra por negociação',
                'operation' => 'debit'
            ],
            [
                'name' => 'mulct_purchase',
                'name_label' => 'Compra por arremate',
                'operation' => 'debit'
            ],
            [
                'name' => 'negotiation_sale',
                'name_label' => 'Venda por negociação',
                'operation' => 'credit'
            ],
            [
                'name' => 'mulct_sale',
                'name_label' => 'Venda por arremate',
                'operation' => 'credit'
            ],
            [
                'name' => 'initial_credit',
                'name_label' => 'Crédito inicial',
                'operation' => 'credit'
            ],
        ]);
    }
}
