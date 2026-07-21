<?php

use App\Enums\BatchStatus;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Services\BatchIntakeService;

beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->route = Route::create([
        'name' => 'Dubai → Syria',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $damascus->id,
    ]);

    $this->batch = Batch::create([
        'reference' => 'BCH-2026-0001',
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
    ]);

    $this->stranger = Customer::create(['name' => 'غريب', 'phone' => '+971500000099']);

    $this->service = app(BatchIntakeService::class);
});

test('an agreed rate supplied at intake becomes the customer rate and prices the shipment', function () {
    $shipment = $this->service->receive($this->batch, [
        'customer_id' => $this->stranger->id,
        'recipient_is_customer' => true,
        'agreed_rate_per_kg_cents' => 275,
        'packages' => [['weight_kg' => 2.0, 'description' => null]],
    ]);

    expect($shipment->rate_per_kg_cents)->toBe(275)
        ->and($shipment->final_charge_cents)->toBe(550);

    expect(CustomerRate::where('customer_id', $this->stranger->id)
        ->where('route_id', $this->route->id)
        ->value('rate_per_kg_cents'))->toBe(275);
});

test('an agreed rate never overwrites a rate that already exists', function () {
    CustomerRate::create([
        'customer_id' => $this->stranger->id,
        'route_id' => $this->route->id,
        'rate_per_kg_cents' => 300,
    ]);

    $shipment = $this->service->receive($this->batch, [
        'customer_id' => $this->stranger->id,
        'recipient_is_customer' => true,
        // Ignored. Changing an agreed rate is the rates screen's job, behind
        // its confirmation — not a side effect of receiving boxes.
        'agreed_rate_per_kg_cents' => 999,
        'packages' => [['weight_kg' => 1.0, 'description' => null]],
    ]);

    expect($shipment->rate_per_kg_cents)->toBe(300);

    expect(CustomerRate::where('customer_id', $this->stranger->id)
        ->where('route_id', $this->route->id)
        ->value('rate_per_kg_cents'))->toBe(300);
});

test('a zero or negative agreed rate is refused and nothing is written', function () {
    expect(fn () => $this->service->receive($this->batch, [
        'customer_id' => $this->stranger->id,
        'recipient_is_customer' => true,
        'agreed_rate_per_kg_cents' => 0,
        'packages' => [['weight_kg' => 1.0, 'description' => null]],
    ]))->toThrow(DomainException::class);

    expect(Shipment::count())->toBe(0)
        ->and(CustomerRate::count())->toBe(0);
});
