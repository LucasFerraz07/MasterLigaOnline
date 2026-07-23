<?php

use App\Http\Controllers\Payment\Payment;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api')->group(function () {
    Route::post('/', [Payment::class, 'store']);
    Route::get('/{id}', [Payment::class, 'show']);
});
