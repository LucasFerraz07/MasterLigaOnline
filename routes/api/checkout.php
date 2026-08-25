<?php

use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api')->group(function () {
    Route::post('/', [CheckoutController::class, 'store']);
    Route::get('/{checkout}', [CheckoutController::class, 'show']);
    Route::post('/{checkout}/payments', [CheckoutController::class, 'pay']);
});
