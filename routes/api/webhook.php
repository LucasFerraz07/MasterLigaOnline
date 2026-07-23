<?php

use App\Http\Controllers\Webhook\AbacatePayWebhook;
use Illuminate\Support\Facades\Route;

Route::post('/abacate-pay', [AbacatePayWebhook::class, 'handle']);
