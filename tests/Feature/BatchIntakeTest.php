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
    $this->damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->route = Route::create([
        'name' => 'Dubai → Syria',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);

    $this->batch = Batch::create([
        'reference' => 'BCH-2026-0001',
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);

    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->route->id,
        'rate_per_kg_cents' => 300,
    ]);

    $this->service = app(BatchIntakeService::class);
});

test('intake creates a priced shipment attached to the batch', function () {
    $shipment = $this->service->receive($this->batch, [
        'customer_id' => $this->customer->id,
        'recipient_is_customer' => true,
        'packages' => [
            ['weight_kg' => 2.5, 'description' => 'ملابس'],
            ['weight_kg' => 1.5, 'description' => null],
        ],
    ]);

    expect($shipment->batch_id)->toBe($this->batch->id)
        ->and($shipment->packages)->toHaveCount(2)
        ->and((string) $shipment->total_weight_kg)->toBe('4.0000')
        ->and($shipment->rate_per_kg_cents)->toBe(300)
        // 4.0 kg × 300 cents
        ->and($shipment->final_charge_cents)->toBe(1200);
});

test('the destination comes from the batch route, not from the caller', function () {
    $shipment = $this->service->receive($this->batch, [
        'customer_id' => $this->customer->id,
        'recipient_is_customer' => true,
        'packages' => [['weight_kg' => 1.0, 'description' => null]],
    ]);

    expect($shipment->destination_warehouse_id)->toBe($this->damascus->id);
});

test('the recipient defaults to the customer', function () {
    $shipment = $this->service->receive($this->batch, [
        'customer_id' => $this->customer->id,
        'recipient_is_customer' => true,
        'packages' => [['weight_kg' => 1.0, 'description' => null]],
    ]);

    expect($shipment->recipient_name)->toBe('أحمد')
        ->and($shipment->recipient_phone)->toBe('+971500000001');
});

test('a different recipient is kept when given', function () {
    $shipment = $this->service->receive($this->batch, [
        'customer_id' => $this->customer->id,
        'recipient_is_customer' => false,
        'recipient_name' => 'سامي',
        'recipient_phone' => '+9613000001',
        'packages' => [['weight_kg' => 1.0, 'description' => null]],
    ]);

    expect($shipment->recipient_name)->toBe('سامي')
        ->and($shipment->recipient_phone)->toBe('+9613000001');
});

test('every package gets its own barcode', function () {
    $shipment = $this->service->receive($this->batch, [
        'customer_id' => $this->customer->id,
        'recipient_is_customer' => true,
        'packages' => [
            ['weight_kg' => 1.0, 'description' => null],
            ['weight_kg' => 2.0, 'description' => null],
        ],
    ]);

    $barcodes = $shipment->packages->pluck('barcode');

    expect($barcodes)->toHaveCount(2)
        ->and($barcodes->unique())->toHaveCount(2)
        ->and($barcodes->filter())->toHaveCount(2);
});

test('at least one package is required', function () {
    expect(fn () => $this->service->receive($this->batch, [
        'customer_id' => $this->customer->id,
        'recipient_is_customer' => true,
        'packages' => [],
    ]))->toThrow(DomainException::class);
});

test('a customer with no rate for this route is refused and nothing is written', function () {
    $stranger = Customer::create(['name' => 'غريب', 'phone' => '+971500000099']);

    $before = Shipment::count();

    expect(fn () => $this->service->receive($this->batch, [
        'customer_id' => $stranger->id,
        'recipient_is_customer' => true,
        'packages' => [['weight_kg' => 1.0, 'description' => null]],
    ]))->toThrow(DomainException::class);

    // The whole intake is one transaction: a refusal leaves no orphan
    // shipment or package behind.
    expect(Shipment::count())->toBe($before);
});
