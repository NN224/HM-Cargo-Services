<?php

use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\BatchIntakeService;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => UserRole::Administrator,
    ]);

    $this->dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->syria = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->route = Route::create([
        'name' => 'Dubai → Syria',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->syria->id,
    ]);

    $this->customer = Customer::create([
        'name' => 'Sami',
        'phone' => '+971500000001',
    ]);

    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->route->id,
        'rate_per_kg_cents' => 925, // $9.25 / kg
    ]);

    $this->batch = Batch::create([
        'route_id' => $this->route->id,
    ]);
});

test('intake calculates final charge with custom per package rates', function () {
    $service = app(BatchIntakeService::class);

    $shipment = $service->receive($this->batch, [
        'customer_id' => $this->customer->id,
        'recipient_is_customer' => true,
        'packages' => [
            [
                'weight_kg' => '2.0000',
                'description' => 'Shein Clothes',
                'custom_rate_per_kg' => null, // Default: $9.25 * 2 = $18.50 (1850 cents)
            ],
            [
                'weight_kg' => '1.0000',
                'description' => 'Cosmetics',
                'custom_rate_per_kg' => '18.00', // Custom: $18.00 * 1 = $18.00 (1800 cents)
            ],
        ],
    ], $this->admin);

    // Total expected = 1850 + 1800 = 3650 cents ($36.50)
    expect($shipment->final_charge_cents)->toBe(3650);
});
