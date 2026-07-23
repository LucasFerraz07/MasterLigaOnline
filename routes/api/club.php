<?php

use App\Http\Controllers\Club\Club;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:club.view')->group(function () {
    Route::get('/', [Club::class, 'index']);
    Route::get('/{id}', [Club::class, 'show']);
});

Route::middleware('auth.api', 'permission:club.create', 'league.active')->group(function () {
    Route::post('/', [Club::class, 'store']);
});

Route::middleware('auth.api', 'permission:club.update', 'league.active')->group(function () {
    Route::put('/{id}', [Club::class, 'update']);
});

Route::middleware('auth.api', 'permission:club.delete', 'league.active')->group(function () {
    Route::delete('/{id}', [Club::class, 'destroy']);
});