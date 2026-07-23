<?php

use App\Http\Controllers\Subscription\Subscription;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api')->group(function () {
    Route::get('/catalog', [Subscription::class, 'catalog']);
});

Route::middleware('auth.api', 'permission:subscription.view')->group(function () {
    Route::get('/', [Subscription::class, 'index']);
    Route::get('/{id}', [Subscription::class, 'show']);
});

Route::middleware('auth.api', 'permission:subscription.create')->group(function () {
    Route::post('/', [Subscription::class, 'store']);
});

Route::middleware('auth.api', 'permission:subscription.update')->group(function () {
    Route::put('/{id}', [Subscription::class, 'update']);
});

Route::middleware('auth.api', 'permission:subscription.delete')->group(function () {
    Route::delete('/{id}', [Subscription::class, 'destroy']);
});
