<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Internal\AirbnbIntegrationController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('internal/airbnb')
    ->middleware(['airbnb.enabled', 'auth:sanctum', 'integration.client', 'abilities:airbnb'])
    ->group(function (): void {
        Route::get('/properties', [AirbnbIntegrationController::class, 'availableProperties']);
        Route::post('/properties/{property:uuid}/claim', [AirbnbIntegrationController::class, 'claimProperty']);
        Route::post('/properties/{property:uuid}/release', [AirbnbIntegrationController::class, 'releaseProperty']);
        Route::post('/financial-events', [AirbnbIntegrationController::class, 'storeFinancialEvent']);
    });
