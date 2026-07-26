<?php

use App\Http\Controllers\CustomerStatementController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/track/{token}', [TrackingController::class, 'show'])
    ->middleware('throttle:20,1')
    ->name('tracking.show');

$filamentPath = env('FILAMENT_PATH', '');
if ($filamentPath !== '') {
    Route::redirect('/login', '/'.$filamentPath.'/login')->name('login');
} else {
    Route::get('/system-login', fn () => redirect()->route('filament.admin.auth.login'))->name('login');
}

Route::middleware(['auth'])->group(function () {
    Route::get('/labels/packages/{package}', [LabelController::class, 'printPackage'])
        ->name('labels.package');

    Route::get('/labels/shipments/{shipment}', [LabelController::class, 'printShipment'])
        ->name('labels.shipment');

    Route::get('/customers/{customer}/statement/print', [CustomerStatementController::class, 'printStatement'])
        ->name('customers.statement.print');
});
