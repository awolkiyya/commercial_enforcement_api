<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrivateFileController;
use App\Modules\Markets\Controllers\MarketCategoryController;
use App\Modules\Markets\Controllers\CityMarketPriceController;


// --------------------------------------
// API HEALTH CHECK
// --------------------------------------
Route::get('/ping', function () {
    return response()->json([
        'message' => 'KPI server running'
    ]);
});

// --------------------------------------
// API VERSION 1
// --------------------------------------
Route::prefix('v1')->group(function () {

    /**
     * PUBLIC ROUTES (NO AUTH)
     */
    require base_path('routes/api/v1/auth.php');
    Route::get('/categories', [MarketCategoryController::class, 'index']);
    Route::get('/prices', [CityMarketPriceController::class, 'index']);



    /**
     * PROTECTED ROUTES (SANCTUM REQUIRED)
     */
    Route::middleware('auth:sanctum')->group(function () {

        require base_path('routes/api/v1/user.php');
        require base_path('routes/api/v1/governance.php');
        require base_path('routes/api/v1/reference.php');
        require base_path('routes/api/v1/business.php');
        require base_path('routes/api/v1/inspection.php');

        require base_path('routes/api/v1/enforcement.php');
        require base_path('routes/api/v1/reports.php');
        require base_path('routes/api/v1/markets.php');
        require base_path('routes/api/v1/dashboard.php');


    });

    require base_path('routes/api/v1/public.php');



    Route::get('/private-file/{file}', [PrivateFileController::class, 'show'])
    ->where('file', '.*');
});


