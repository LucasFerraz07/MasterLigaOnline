<?php

use App\Http\Controllers\Season\Season;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:season.view')->group(function () {
    Route::get('/', [Season::class, 'index']);
    Route::get('/{id}', [Season::class, 'show']);
});

Route::middleware('auth.api', 'permission:season.create')->group(function () {
    Route::post('/', [Season::class, 'store']);
});

Route::middleware('auth.api', 'permission:season.update')->group(function () {
    Route::put('/advance-phase/{id}', [Season::class, 'advancePhase']);
});
