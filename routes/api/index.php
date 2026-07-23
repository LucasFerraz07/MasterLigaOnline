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
Route::prefix('transfer')->group(base_path('routes/api/transfer.php'));
Route::prefix('transfer-bid')->group(base_path('routes/api/transfer-bid.php'));
Route::prefix('financial-transaction')->group(base_path('routes/api/financial-transaction.php'));
Route::prefix('mulct')->group(base_path('routes/api/mulct.php'));
Route::prefix('payment')->group(base_path('routes/api/payment.php'));
Route::prefix('webhooks')->group(base_path('routes/api/webhook.php'));
