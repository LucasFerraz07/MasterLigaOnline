<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(base_path('routes/api/auth.php'));
Route::prefix('subscription')->group(base_path('routes/api/subscription.php'));
Route::prefix('club')->group(base_path('routes/api/club.php'));
Route::prefix('transaction-type')->group(base_path('routes/api/transaction-type.php'));
Route::prefix('league')->group(base_path('routes/api/league.php'));
Route::prefix('league-category-price')->group(base_path('routes/api/league-category-price.php'));
Route::prefix('season')->group(base_path('routes/api/season.php'));
Route::prefix('user')->group(base_path('routes/api/user.php'));
Route::prefix('player')->group(base_path('routes/api/player.php'));
Route::prefix('squad')->group(base_path('routes/api/squad.php'));
Route::prefix('club-identity')->group(base_path('routes/api/club-identity.php'));
