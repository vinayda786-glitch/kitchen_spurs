<?php

use App\Http\Controllers\Api\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/analytics')->group(function () {
    // Ingestion endpoint for browser & external client events
    Route::post('/events', [AnalyticsController::class, 'ingest'])->name('api.analytics.ingest');

    // Dashboard reporting statistics endpoint
    Route::get('/stats', [AnalyticsController::class, 'stats'])->name('api.analytics.stats');

    // Evaluation demo helpers
    Route::post('/simulate', [AnalyticsController::class, 'simulateTraffic'])->name('api.analytics.simulate');
    Route::post('/reset', [AnalyticsController::class, 'resetData'])->name('api.analytics.reset');
});

