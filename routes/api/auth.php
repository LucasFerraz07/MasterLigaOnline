<?php

use App\Http\Controllers\Auth\Auth;
use Illuminate\Support\Facades\Route;

Route::post('/login', [Auth::class, 'login']);

Route::middleware('auth.api')->group(function () {
    Route::get('/me', [Auth::class, 'me']);
    Route::post('/refresh', [Auth::class, 'refresh']);
    Route::post('/logout', [Auth::class, 'logout']);
});
