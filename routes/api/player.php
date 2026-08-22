<?php

use App\Http\Controllers\Player\Player;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:player.view')->group(function () {
    Route::get('/', [Player::class, 'index']);
    Route::get('/{id}', [Player::class, 'show']);
});

Route::middleware('auth.api', 'permission:player.import')->group(function () {
    Route::post('/import', [Player::class, 'import']);
});

Route::middleware('auth.api', 'permission:player.update', 'league.active')->group(function () {
    Route::post('image/{id}', [Player::class, 'uploadImage']);
});
