<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Inspection\Controllers\InspectionController;
use App\Modules\Inspection\Controllers\ComplaintController;

Route::prefix('publics')->group(function () {

    // =========================
    // TRACK ACTIVE INSPECTION (PUBLIC)
    // =========================
    Route::get('/inspection/{inspectionNumber}', [
        InspectionController::class,
        'trackPublicInspection'
    ]);

    // =========================
    // SUBMIT COMPLAINT (PUBLIC)
    // =========================
    Route::post('/inspection/{inspectionId}/complaints', [
        ComplaintController::class,
        'store'
    ]);

    // =========================
    // LIST COMPLAINTS FOR INSPECTION (PUBLIC VIEW)
    // =========================
    Route::get('/inspection/{inspectionId}/complaints', [
        ComplaintController::class,
        'index'
    ]);

    // =========================
    // VIEW SINGLE COMPLAINT (PUBLIC)
    // =========================
    Route::get('/complaints/{complaintId}', [
        ComplaintController::class,
        'show'
    ]);
});