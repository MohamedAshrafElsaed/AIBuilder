<?php

use App\Http\Controllers\FacebookConversionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('facebook/conversion')
    ->middleware(['auth:sanctum', 'throttle:60,1'])
    ->group(function () {
        Route::post('/event', [FacebookConversionController::class, 'sendEvent']);
        Route::post('/batch', [FacebookConversionController::class, 'sendBatchEvents']);
        Route::post('/test', [FacebookConversionController::class, 'testEvent']);
    });
