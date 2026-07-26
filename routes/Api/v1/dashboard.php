<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Dashboard\Controllers\DashboardController;

Route::prefix('dashboard')->controller(DashboardController::class)->group(function () {

    Route::get('/', [DashboardController::class, 'index']);

    Route::get('/statistics', [DashboardController::class, 'statistics']);

    Route::get('/charts', [DashboardController::class, 'charts']);

    Route::get('/geo', [DashboardController::class, 'geo']);

    Route::get('/activities', [DashboardController::class, 'activities']);

    Route::get('/alerts', [DashboardController::class, 'alerts']);


});