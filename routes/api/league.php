<?php

use App\Http\Controllers\League\League;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:league.view')->group(function () {
    Route::get('/', [League::class, 'index']);
    Route::get('/{id}', [League::class, 'show']);
});

Route::middleware('auth.api', 'permission:league.update')->group(function () {
    Route::put('/{id}', [League::class, 'update']);
});

Route::middleware('auth.api', 'permission:league.delete')->group(function () {
    Route::delete('/{id}', [League::class, 'delete']);
});

Route::middleware('auth.api', 'permission:league.restore')->group(function () {
    Route::patch('/{id}/restore', [League::class, 'restore']);
});

Route::middleware('auth.api', 'permission:league.force-delete')->group(function () {
    Route::delete('/{id}/destroy', [League::class, 'destroy']);
});
