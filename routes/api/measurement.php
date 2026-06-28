<?php

use App\Http\Controllers\Measurement\Measurement;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:measurement.view')->group(function () {
    Route::get('/', [Measurement::class, 'index']);
});

Route::middleware('auth.api')->group(function () {
    Route::post('/', [Measurement::class, 'store']);
});