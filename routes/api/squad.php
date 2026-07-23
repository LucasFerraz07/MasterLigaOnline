<?php

use App\Http\Controllers\Squad\Squad;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:squad.view')->group(function () {
    Route::get('/', [Squad::class, 'index']);
    Route::get('/{id}', [Squad::class, 'show']);
});

Route::middleware('auth.api', 'permission:squad.update', 'league.active')->group(function () {
    Route::put('/reajuste-salario/{id}', [Squad::class, 'adjustSalary']);
});

Route::middleware('auth.api', 'permission:squad.create', 'league.active')->group(function () {
    Route::post('/comprar-jogador-livre', [Squad::class, 'buyFreeAgent']);
});
