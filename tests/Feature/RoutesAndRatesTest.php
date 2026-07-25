<?php

use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\Warehouse;
use Illuminate\Database\UniqueConstraintViolationException;

beforeEach(function () {
    $this->dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->beirut = Warehouse::create(['name' => 'Beirut', 'location' => 'Lebanon']);
    $this->damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);
});

test('a direct route and a transit route to the same destination are distinct records', function () {
    $direct = Route::create([
        'name' => 'Dubai → Syria (direct)',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);

    $viaBeirut = Route::create([
        'name' => 'Dubai → Beirut → Syria',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
        'transit_warehouse_id' => $this->beirut->id,
    ]);

    expect($direct->id)->not->toBe($viaBeirut->id)
        ->and($direct->hasTransit())->toBeFalse()
        ->and($viaBeirut->hasTransit())->toBeTrue();
});

test('a route cannot start and end at the same warehouse', function () {
    Route::create([
        'name' => 'Nowhere',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->dubai->id,
    ]);
})->throws(InvalidArgumentException::class);

test('a transit warehouse cannot duplicate the origin or destination', function () {
    Route::create([
        'name' => 'Bad transit',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
        'transit_warehouse_id' => $this->damascus->id,
    ]);
})->throws(InvalidArgumentException::class);

test('the same customer may hold different rates on direct and transit routes', function () {
    $customer = Customer::create(['name' => 'Ahmad', 'phone' => '+971500000001']);

    $direct = Route::create([
        'name' => 'Dubai → Syria (direct)',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);
    $viaBeirut = Route::create([
        'name' => 'Dubai → Beirut → Syria',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
        'transit_warehouse_id' => $this->beirut->id,
    ]);

    CustomerRate::create([
        'customer_id' => $customer->id, 'route_id' => $direct->id,
        'rate_per_kg_cents' => 450,
    ]);
    CustomerRate::create([
        'customer_id' => $customer->id, 'route_id' => $viaBeirut->id,
        'rate_per_kg_cents' => 375,
    ]);

    expect($customer->rateForRoute($direct)->rate_per_kg_cents)->toBe(450)
        ->and($customer->rateForRoute($viaBeirut)->rate_per_kg_cents)->toBe(375);
});

test('a customer has at most one rate per route', function () {
    $customer = Customer::create(['name' => 'Sara', 'phone' => '+971500000002']);
    $route = Route::create([
        'name' => 'Dubai → Lebanon',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->beirut->id,
    ]);

    CustomerRate::create([
        'customer_id' => $customer->id, 'route_id' => $route->id,
        'rate_per_kg_cents' => 400,
    ]);

    CustomerRate::create([
        'customer_id' => $customer->id, 'route_id' => $route->id,
        'rate_per_kg_cents' => 500,
    ]);
})->throws(UniqueConstraintViolationException::class);

test('a rate must be a positive number of cents', function () {
    $customer = Customer::create(['name' => 'Omar', 'phone' => '+971500000003']);
    $route = Route::create([
        'name' => 'Dubai → Beirut',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->beirut->id,
    ]);

    CustomerRate::create([
        'customer_id' => $customer->id, 'route_id' => $route->id,
        'rate_per_kg_cents' => 0,
    ]);
})->throws(InvalidArgumentException::class);

test('a missing customer rate is reported as null rather than defaulted', function () {
    $customer = Customer::create(['name' => 'Layla', 'phone' => '+971500000004']);
    $route = Route::create([
        'name' => 'Dubai → Damascus',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);

    // Batch assignment must refuse to price this shipment, never guess a rate.
    expect($customer->rateForRoute($route))->toBeNull();
});

test('customer phone numbers are unique', function () {
    Customer::create(['name' => 'First', 'phone' => '+971500000005']);
    Customer::create(['name' => 'Duplicate', 'phone' => '+971500000005']);
})->throws(UniqueConstraintViolationException::class);

test('a customer is not a credit customer unless explicitly marked', function () {
    $customer = Customer::create(['name' => 'Cash Only', 'phone' => '+971500000006']);

    expect($customer->is_credit_customer)->toBeFalse()
        ->and($customer->is_active)->toBeTrue();
});
