<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Governance\Controllers\CityController;
use App\Modules\Governance\Controllers\SubcityController;
use App\Modules\Governance\Controllers\WeredaController;
use App\Modules\Governance\Controllers\SectorController;

Route::prefix('governance')->group(function () {

    /**
     * =====================================================
     * CITY LEVEL
     * =====================================================
     */
    Route::prefix('cities')->group(function () {
        Route::get('/', [CityController::class, 'index']);
        // Route::post('/', [CityController::class, 'store']);
        // Route::get('/{city}', [CityController::class, 'show']);
        // Route::put('/{city}', [CityController::class, 'update']);
        // Route::delete('/{city}', [CityController::class, 'destroy']);
    });

    /**
     * =====================================================
     * SUBCITY LEVEL
     * =====================================================
     */
    Route::prefix('subcities')->group(function () {
        Route::get('/', [SubcityController::class, 'index']);
        // Route::post('/', [SubcityController::class, 'store']);
        // Route::get('/{subcity}', [SubcityController::class, 'show']);
        // Route::put('/{subcity}', [SubcityController::class, 'update']);
        // Route::delete('/{subcity}', [SubcityController::class, 'destroy']);
    });

    /**
     * =====================================================
     * WEREDA LEVEL
     * =====================================================
     */
    Route::prefix('weredas')->group(function () {
        Route::get('/', [WeredaController::class, 'index']);
        // Route::post('/', [WeredaController::class, 'store']);
        // Route::get('/{wereda}', [WeredaController::class, 'show']);
        // Route::put('/{wereda}', [WeredaController::class, 'update']);
        // Route::delete('/{wereda}', [WeredaController::class, 'destroy']);
    });


});