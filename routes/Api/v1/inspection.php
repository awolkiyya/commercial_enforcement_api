<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Inspection\Controllers\InspectionController;
use App\Modules\Inspection\Controllers\ClosureRequestController;
use App\Modules\Inspection\Controllers\EscalationController;
use App\Modules\Inspection\Controllers\InspectionResolutionController;

/*
|--------------------------------------------------------------------------
| INSPECTION ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('inspections')->group(function () {

    // =========================
    // CRUD
    // =========================
    Route::post('/', [InspectionController::class, 'store']);
    Route::get('/', [InspectionController::class, 'index']);

    Route::get('/my/list', [InspectionController::class, 'myInspections']);
    Route::get('/business/{businessId}', [InspectionController::class, 'byBusiness']);
    Route::get('/inspectors', [InspectionController::class, 'inspectors']);

    // =========================
    // EXPORT
    // =========================
    Route::get('/export', [InspectionController::class, 'export']);

    // =========================
    // DASHBOARD
    // =========================
    Route::get('/dashboard', [InspectionController::class, 'dashboard']);
    Route::get('/dashboard/charts', [InspectionController::class, 'charts']);

    // =========================
    // ESCALATION
    // =========================
    Route::post('/{inspection}/escalate', [EscalationController::class, 'escalate']);

    // =========================
    // CLOSURE REQUESTS (GLOBAL)
    // =========================
    Route::get('/closure-requests', [ClosureRequestController::class, 'index']);

    Route::post('/{inspection}/closure-requests', [ClosureRequestController::class, 'store']);

    Route::patch('/closure-requests/{closureRequest}/decision', [
        ClosureRequestController::class,
        'makeDecision'
    ]);

    // =========================
    // 🔥 RESOLUTION (FULL REST + LIST)
    // =========================

    // LIST ALL RESOLUTIONS (GLOBAL)
    Route::get('/resolutions', [
        InspectionResolutionController::class,
        'index'
    ]);

    // SINGLE INSPECTION RESOLUTION
    Route::get('/{inspection}/resolution', [
        InspectionResolutionController::class,
        'show'
    ]);

    // CREATE RESOLUTION
    Route::post('/{inspection}/resolution', [
        InspectionResolutionController::class,
        'store'
    ]);

    // UPDATE RESOLUTION (1:1 so NO resolution id needed)
    Route::put('/{inspection}/resolution', [
        InspectionResolutionController::class,
        'update'
    ]);

    // =========================
    // MUST BE LAST (IMPORTANT)
    // =========================
    Route::get('/{inspection}', [InspectionController::class, 'show']);
    Route::put('/{inspection}', [InspectionController::class, 'update']);
});