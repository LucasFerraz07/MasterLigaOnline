<?php

use App\Http\Controllers\PlanController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:plan.view')->get('/', [PlanController::class, 'index']);
Route::middleware('auth.api', 'permission:plan.create')->post('/', [PlanController::class, 'store']);
Route::middleware('auth.api', 'permission:plan.update')->group(function () {
    Route::put('/{plan}', [PlanController::class, 'update']);
    Route::post('/{plan}/prices', [PlanController::class, 'storePrice']);
    Route::patch('/prices/{price}/deactivate', [PlanController::class, 'deactivatePrice']);
});
