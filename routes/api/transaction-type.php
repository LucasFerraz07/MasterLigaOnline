<?php

use App\Http\Controllers\TransactionType\TransactionType;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:transaction-type.view')->group(function () {
    Route::get('/', [TransactionType::class, 'index']);
});

Route::middleware('auth.api', 'permission:transaction-type.create', 'league.active')->group(function () {
    Route::post('/', [TransactionType::class, 'store']);
});

Route::middleware('auth.api', 'permission:transaction-type.update', 'league.active')->group(function () {
    Route::put('/{id}', [TransactionType::class, 'update']);
});

Route::middleware('auth.api', 'permission:transaction-type.delete', 'league.active')->group(function () {
    Route::delete('/{id}', [TransactionType::class, 'destroy']);
});