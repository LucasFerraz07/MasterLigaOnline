<?php

use App\Http\Controllers\FinancialTransaction\FinancialTransaction;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:financial-transaction.view')->group(function () {
    Route::get('/', [FinancialTransaction::class, 'index']);
    Route::get('/{id}', [FinancialTransaction::class, 'show']);
});
