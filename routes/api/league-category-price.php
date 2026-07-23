<?php

use App\Http\Controllers\LeagueCategoryPrice\LeagueCategoryPrice;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:league-category-price.view')->group(function () {
    Route::get('/', [LeagueCategoryPrice::class, 'index']);
});

Route::middleware('auth.api', 'permission:league-category-price.update', 'league.active')->group(function () {
    Route::put('/{id}', [LeagueCategoryPrice::class, 'update']);
});
