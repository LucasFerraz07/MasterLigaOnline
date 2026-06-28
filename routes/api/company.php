<?php

use App\Http\Controllers\Company\Company;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:company.view')->group(function () {
    Route::get('/', [Company::class, 'index']);
});

Route::middleware('auth.api', 'permission:company.create')->group(function () {
    Route::post('/', [Company::class, 'store']);
});