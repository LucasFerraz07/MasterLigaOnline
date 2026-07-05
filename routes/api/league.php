<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\League\League;

Route::middleware('auth.api', 'permission:league.view')->group(function () {
    Route::get('/', [League::class, 'index']);
    Route::get('/{id}', [League::class, 'show']);
});

Route::middleware('auth.api', 'permission:league.create')->group(function () {
    Route::post('/', [League::class, 'store']);
});

Route::middleware('auth.api', 'permission:league.update')->group(function () {
    Route::put('/{id}', [League::class, 'update']);
});

Route::middleware('auth.api', 'permission:league.delete')->group(function () {
    Route::delete('/{id}', [League::class, 'destroy']);
});