<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Markets\Controllers\MarketCategoryController;
use App\Modules\Markets\Controllers\MarketItemController;
use App\Modules\Markets\Controllers\MarketPriceHistoryController;
use App\Modules\Markets\Controllers\CityMarketPriceController;



/*
|--------------------------------------------------------------------------
| Market Module Routes
|--------------------------------------------------------------------------
| Used for marketplace system:
| - Categories
| - Items
| - Market Prices
|
| Prefix: /api/market
|--------------------------------------------------------------------------
*/

Route::prefix('markets')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | MARKET CATEGORIES
    |--------------------------------------------------------------------------
    */
    Route::get('/categories/{id}', [MarketCategoryController::class, 'show']);
    Route::post('/categories', [MarketCategoryController::class, 'store']);
    Route::put('/categories/{id}', [MarketCategoryController::class, 'update']);
    Route::delete('/categories/{id}', [MarketCategoryController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | MARKET ITEMS
    |--------------------------------------------------------------------------
    */

    Route::get('/items', [MarketItemController::class, 'index']);
    Route::get('/items/{id}', [MarketItemController::class, 'show']);
    Route::post('/items', [MarketItemController::class, 'store']);
    Route::put('/items/{id}', [MarketItemController::class, 'update']);
    Route::delete('/items/{id}', [MarketItemController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | MARKET PRICES
    |--------------------------------------------------------------------------
    */

    // 👇 IMPORTANT: history MUST come BEFORE {id} routes in other modules too
    Route::get('/prices/history', [MarketPriceHistoryController::class, 'index']);

    Route::get('/prices', [CityMarketPriceController::class, 'index']);
    Route::get('/prices/{id}', [CityMarketPriceController::class, 'show']);
    Route::post('/prices', [CityMarketPriceController::class, 'store']);
    Route::put('/prices/{id}', [CityMarketPriceController::class, 'update']);
    Route::delete('/prices/{id}', [CityMarketPriceController::class, 'destroy']);

});