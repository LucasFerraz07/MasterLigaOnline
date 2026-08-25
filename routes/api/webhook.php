<?php

use App\Http\Controllers\MercadoPagoWebhookController;
use App\Http\Controllers\Webhook\ImportPlayersWebhook;
use Illuminate\Support\Facades\Route;

Route::post('/mercado-pago', MercadoPagoWebhookController::class);
Route::post('/players/import', [ImportPlayersWebhook::class, 'handle']);
