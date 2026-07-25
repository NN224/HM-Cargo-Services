<?php

use App\Models\Batch;
use App\Models\Route;
use App\Models\Warehouse;

beforeEach(function () {
    $this->dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->route = Route::create([
        'name' => 'Dubai → Syria',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);
});

test('batch uses custom reference when provided', function () {
    $batch = Batch::create([
        'route_id' => $this->route->id,
        'reference' => 'CUSTOM-TRUCK-101',
    ]);

    expect($batch->reference)->toBe('CUSTOM-TRUCK-101');
});

test('batch auto generates reference when left empty', function () {
    $batch = Batch::create([
        'route_id' => $this->route->id,
        'reference' => null,
    ]);

    expect($batch->reference)->toStartWith('BCH-');
});
