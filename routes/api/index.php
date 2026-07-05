<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(base_path('routes/api/auth.php'));
Route::prefix('subscription')->group(base_path('routes/api/subscription.php'));
Route::prefix('club')->group(base_path('routes/api/club.php'));
Route::prefix('transaction-type')->group(base_path('routes/api/transaction-type.php'));
Route::prefix('league')->group(base_path('routes/api/league.php'));