<?php

use App\Http\Controllers\Vitalab\Vitalab;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:vitalab.view')->group(function () {
    Route::get('/', [Vitalab::class, 'index']);
});

Route::middleware('auth.api', 'permission:vitalab.create')->group(function () {
    Route::post('/', [Vitalab::class, 'store']);
});