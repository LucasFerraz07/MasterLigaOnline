<?php

use App\Http\Controllers\Notification\Notification;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api')->group(function () {
    Route::get('/', [Notification::class, 'index']);
    Route::patch('/{id}/read', [Notification::class, 'markAsRead']);
});
