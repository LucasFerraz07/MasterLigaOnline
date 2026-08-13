<?php

use App\Http\Controllers\Game\Game;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:match.view')->group(function () {
    Route::get('/', [Game::class, 'index']);
    Route::get('/{id}', [Game::class, 'show']);
});

Route::middleware('auth.api', 'permission:match.update', 'league.active')->group(function () {
    Route::put('/{id}/publicar-resultado', [Game::class, 'publishResult']);
});
