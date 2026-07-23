<?php

use App\Http\Controllers\Mulct\Mulct;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:mulct.view')->group(function () {
    Route::get('/controle', [Mulct::class, 'control']);
});

Route::middleware('auth.api', 'permission:mulct.create', 'league.active')->group(function () {
    Route::post('/comprar', [Mulct::class, 'buy']);
});
