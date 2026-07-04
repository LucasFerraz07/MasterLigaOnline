<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(base_path('routes/api/auth.php'));
Route::prefix('subscription')->group(base_path('routes/api/subscription.php'));
Route::prefix('club')->group(base_path('routes/api/club.php'));