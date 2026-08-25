<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Business\Controllers\BusinessController;
use App\Modules\Business\Controllers\BusinessTypeController;

Route::prefix('businesses')->group(function () {

    // =========================
    // BUSINESS TYPES (dropdown / lookup)
    // =========================
    Route::get('/types', [BusinessTypeController::class, 'index']);

    // =========================
    // BUSINESS LIST (pagination + search + filters)
    // =========================
    Route::get('/', [BusinessController::class, 'index']);

    // =========================
    // SCOPED BUSINESS LIST (ONE ROUTE)
    // =========================
    Route::get('/scoped', [BusinessController::class, 'scopedIndex']);

    // =========================
    // SINGLE BUSINESS
    // =========================
    Route::get('/{id}', [BusinessController::class, 'show']);

    // =========================
    // CREATE BUSINESS
    // =========================
    Route::post('/', [BusinessController::class, 'store']);

    // =========================
    // FULL UPDATE
    // =========================
    Route::put('/{id}', [BusinessController::class, 'update']);

    // =========================
    // PARTIAL UPDATE
    // =========================
    Route::patch('/{id}', [BusinessController::class, 'update']);

    // =========================
    // BUSINESS STATUS CONTROL
    // =========================
    Route::patch('/{id}/status', [BusinessController::class, 'changeStatus']);

    // =========================
    // MERGE DUPLICATES
    // =========================
    Route::patch('/{id}/merge', [BusinessController::class, 'merge']);
});