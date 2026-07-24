<?php

use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;

beforeEach(function () {
    $this->dubai = Warehouse::create(['name' => 'دبي', 'location' => 'الإمارات']);
    $this->damascus = Warehouse::create(['name' => 'دمشق', 'location' => 'سوريا']);

    $this->route = Route::create([
        'name' => 'دبي ← دمشق',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);

    $this->batch = Batch::create(['route_id' => $this->route->id]);
    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'wa-tracking-admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => $this->dubai->id,
    ]);
});

test('can mark intake and arrival notification timestamps on shipment', function () {
    $shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'رامي',
        'recipient_phone' => '+96170000001',
        'status' => ShipmentStatus::Pending->value,
    ]);

    expect($shipment->intake_notified_at)->toBeNull()
        ->and($shipment->arrival_notified_at)->toBeNull();

    $shipment->markIntakeNotified();
    expect($shipment->fresh()->intake_notified_at)->not->toBeNull();

    $shipment->markArrivalNotified();
    expect($shipment->fresh()->arrival_notified_at)->not->toBeNull();
});
