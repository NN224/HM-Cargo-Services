<?php

use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

// HM Cargo Services is an administration application, so the root has no
// public landing page of its own. Tracking is the deliberately small public
// surface; its throttle limits token and barcode guessing before auth exists.
Route::redirect('/', 'https://hmcargoservices.com');

Route::get('/track/{token}', [TrackingController::class, 'show'])
    ->middleware('throttle:20,1')
    ->name('tracking.show');

Route::redirect('/login', '/' . env('FILAMENT_PATH', 'portal') . '/login')->name('login');

Route::middleware(['auth'])->group(function () {
    Route::get('/labels/packages/{package}', [\App\Http\Controllers\LabelController::class, 'printPackage'])
        ->name('labels.package');
        
    Route::get('/labels/shipments/{shipment}', [\App\Http\Controllers\LabelController::class, 'printShipment'])
        ->name('labels.shipment');

    Route::get('/customers/{customer}/statement/print', [\App\Http\Controllers\CustomerStatementController::class, 'printStatement'])
        ->name('customers.statement.print');
});
