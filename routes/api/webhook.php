<?php

use App\Http\Controllers\Webhook\AbacatePayWebhook;
use App\Http\Controllers\Webhook\ImportPlayersWebhook;
use Illuminate\Support\Facades\Route;

Route::post('/abacate-pay', [AbacatePayWebhook::class, 'handle']);
Route::post('/players/import', [ImportPlayersWebhook::class, 'handle']);
