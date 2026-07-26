<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Controllers\RefreshTokenController;

// --------------------------------------
// AUTH MODULE (v1)
// --------------------------------------

Route::prefix('auth')->name('auth.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public
    |--------------------------------------------------------------------------
    */
    Route::post('/login', [AuthController::class, 'login'])
        ->name('login');

    /*
    |--------------------------------------------------------------------------
    | Protected (Sanctum)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('logout');

        Route::get('/me', [AuthController::class,'me'])
            ->name('me');

        Route::post('/refresh-token', [RefreshTokenController::class,'refresh'])
            ->name('refresh');
    });
});