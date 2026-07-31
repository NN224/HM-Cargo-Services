<?php

use App\Http\Controllers\TrackingApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:30,1')->group(function () {
    // Lookup by public token (used by the direct wa.me link)
    Route::get('/track/{token}', [TrackingApiController::class, 'show'])
        ->name('api.tracking.show');

    // Lookup by human-readable reference (used by the search box on the site)
    Route::get('/track/ref/{reference}', [TrackingApiController::class, 'showByReference'])
        ->name('api.tracking.showByReference');
});
