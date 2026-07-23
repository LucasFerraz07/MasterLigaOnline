<?php

use App\Http\Controllers\User\User;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:user.view')->group(function () {
    Route::get('/', [User::class, 'index']);
    Route::get('/{id}', [User::class, 'show']);
});

Route::middleware('auth.api', 'permission:user.create', 'league.active')->group(function () {
    Route::post('/', [User::class, 'store']);
});

Route::middleware('auth.api', 'permission:user.balance', 'league.active')->group(function () {
    Route::put('/ajustar-saldo', [User::class, 'adjustBalance']);
});

Route::middleware('auth.api', 'permission:user.update', 'league.active')->group(function () {
    Route::put('/{id}', [User::class, 'update']);
});

Route::middleware('auth.api', 'permission:user.delete', 'league.active')->group(function () {
    Route::delete('/{id}', [User::class, 'destroy']);
});
