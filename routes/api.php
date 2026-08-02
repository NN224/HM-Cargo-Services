<?php

use App\Http\Controllers\TrackingApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:30,1')->group(function () {
    // Lookup by public token only. Lookup by the readable reference was
    // removed: references are sequential (HM-2026-000001), so the endpoint
    // let the whole range be walked for recipient names and balances.
    Route::get('/track/{token}', [TrackingApiController::class, 'show'])
        ->name('api.tracking.show');
});
