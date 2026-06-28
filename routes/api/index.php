<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(base_path('routes/api/auth.php'));
Route::prefix('company')->group(base_path('routes/api/company.php'));
Route::prefix('vitalab')->group(base_path('routes/api/vitalab.php'));
Route::prefix('measurement')->group(base_path('routes/api/measurement.php'));