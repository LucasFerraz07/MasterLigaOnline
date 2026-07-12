<?php

use App\Http\Controllers\Squad\Squad;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:squad.view')->group(function () {
    Route::get('/', [Squad::class, 'index']);
    Route::get('/{id}', [Squad::class, 'show']);
});

Route::middleware('auth.api', 'permission:squad.update')->group(function () {
    Route::put('/reajuste-salario/{id}', [Squad::class, 'adjustSalary']);
});
