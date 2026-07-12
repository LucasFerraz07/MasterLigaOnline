<?php

use App\Http\Controllers\ClubIdentity\ClubIdentity;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api', 'permission:club-identity.view')->group(function () {
    Route::get('/', [ClubIdentity::class, 'index']);
    Route::get('/{id}', [ClubIdentity::class, 'show']);
});

Route::middleware('auth.api', 'permission:club-identity.update')->group(function () {
    Route::put('/trocar-clube/{id}', [ClubIdentity::class, 'changeClub']);
});
