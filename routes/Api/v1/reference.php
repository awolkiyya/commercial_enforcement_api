<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReferenceDataController;

/*
|--------------------------------------------------------------------------
| Reference Data Routes
|--------------------------------------------------------------------------
| Used by inspector app / frontend to load system constants
| (violation types, penalty types, etc.)
*/

Route::prefix('reference')->group(function () {

    // =========================
    // VIOLATION TYPES
    // =========================
    Route::get('/violation-types', [ReferenceDataController::class, 'violationTypes']);

    // =========================
    // PENALTY / ACTION TYPES
    // =========================
    Route::get('/penalty-types', [ReferenceDataController::class, 'penaltyTypes']);

});