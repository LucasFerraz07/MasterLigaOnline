<?php

use App\Http\Controllers\Transfer\Transfer;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:transfer.view')->group(function () {
    Route::get('/', [Transfer::class, 'index']);
    Route::get('/{id}', [Transfer::class, 'show']);
});
