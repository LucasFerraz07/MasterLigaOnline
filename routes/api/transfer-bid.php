<?php

use App\Http\Controllers\TransferBid\TransferBid;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:transfer-bid.view')->group(function () {
    Route::get('/', [TransferBid::class, 'index']);
    Route::get('/{id}', [TransferBid::class, 'show']);
});

Route::middleware('auth.api', 'permission:transfer-bid.create')->group(function () {
    Route::post('/', [TransferBid::class, 'store']);
});

Route::middleware('auth.api', 'permission:transfer-bid.update')->group(function () {
    Route::put('/{id}/aceitar', [TransferBid::class, 'accept']);
    Route::put('/{id}/recusar', [TransferBid::class, 'reject']);
    Route::put('/{id}/cancelar', [TransferBid::class, 'cancel']);
});
