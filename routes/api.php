<?php

use App\Http\Controllers\Api\FacebookConversionController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::post('/facebook/track-event', [FacebookConversionController::class, 'trackEvent']);
    Route::post('/facebook/track-batch', [FacebookConversionController::class, 'trackBatch']);
});
