<?php

use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

// HM Cargo Services is an administration application, so the root has no
// public landing page of its own. Tracking is the deliberately small public
// surface; its throttle limits token and barcode guessing before auth exists.
Route::redirect('/', '/admin');

Route::get('/track/{token}', [TrackingController::class, 'show'])
    ->middleware('throttle:20,1')
    ->name('tracking.show');
