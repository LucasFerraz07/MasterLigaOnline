<?php

use App\Http\Controllers\Player\Player;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:player.view')->group(function () {
    Route::get('/', [Player::class, 'index']);
    Route::get('/{id}', [Player::class, 'show']);
});
