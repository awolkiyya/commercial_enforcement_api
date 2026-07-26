<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Enforcement\Controllers\InspectionController;
use App\Modules\Enforcement\Controllers\ViolationController;

Route::prefix('enforcement')->group(function () {

    // Inspections
    Route::get('/inspections', [InspectionController::class, 'index']);
    Route::post('/inspections', [InspectionController::class, 'store']);

    // Violations
    Route::get('/violations', [ViolationController::class, 'index']);
    Route::post('/violations', [ViolationController::class, 'store']);
});