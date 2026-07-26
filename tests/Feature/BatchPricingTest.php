<?php

use App\Enums\BatchStatus;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Services\BatchAssignmentService;

beforeEach(function () {
    $this->dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->beirut = Warehouse::create(['name' => 'Beirut', 'location' => 'Lebanon']);
    $this->damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->direct = Route::create([
        'name' => 'Dubai → Syria (direct)',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);

    $this->viaBeirut = Route::create([
        'name' => 'Dubai → Beirut → Syria',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
        'transit_warehouse_id' => $this->beirut->id,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);

    $this->service = app(BatchAssignmentService::class);
});

function shipmentWeighing(float $kg): Shipment
{
    $shipment = Shipment::create([
        'customer_id' => test()->customer->id,
        'recipient_name' => 'سامي',
        'recipient_phone' => '+9613000001',
    ]);
    $shipment->packages()->create(['weight_kg' => $kg]);

    return $shipment->fresh();
}

function batchOn(Route $route): Batch
{
    return Batch::create(['route_id' => $route->id]);
}

test('assigning a shipment prices it from the customer rate for that route', function () {
    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->direct->id,
        'rate_per_kg_cents' => 450,
    ]);

    $shipment = shipmentWeighing(12.5);
    $this->service->assign($shipment, batchOn($this->direct));

    // 12.5 kg × $4.50 = $56.25, kept exactly to the cent.
    expect($shipment->fresh()->computed_charge_cents)->toBe(5625)
        ->and($shipment->fresh()->rate_per_kg_cents)->toBe(450);
});

test('the same customer is charged differently on a transit route', function () {
    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->direct->id, 'rate_per_kg_cents' => 450,
    ]);
    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->viaBeirut->id, 'rate_per_kg_cents' => 375,
    ]);

    $a = shipmentWeighing(10);
    $b = shipmentWeighing(10);

    $this->service->assign($a, batchOn($this->direct));
    $this->service->assign($b, batchOn($this->viaBeirut));

    expect($a->fresh()->computed_charge_cents)->toBe(4500)
        ->and($b->fresh()->computed_charge_cents)->toBe(3750);
});

test('assignment is refused when the customer has no rate for the route', function () {
    // Must never fall back to a default: an invented price is worse than a stop.
    $this->service->assign(shipmentWeighing(10), batchOn($this->direct));
})->throws(DomainException::class);

test('a refused assignment leaves the shipment completely untouched', function () {
    $shipment = shipmentWeighing(10);

    try {
        $this->service->assign($shipment, batchOn($this->direct));
    } catch (DomainException) {
        // expected
    }

    $shipment->refresh();
    expect($shipment->batch_id)->toBeNull()
        ->and($shipment->rate_per_kg_cents)->toBeNull()
        ->and($shipment->computed_charge_cents)->toBeNull();
});

test('a later rate change never rewrites an existing charge', function () {
    $rate = CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->direct->id, 'rate_per_kg_cents' => 450,
    ]);

    $shipment = shipmentWeighing(10);
    $this->service->assign($shipment, batchOn($this->direct));

    $rate->update(['rate_per_kg_cents' => 900]);

    // The snapshot is the whole point of D-007.
    expect($shipment->fresh()->rate_per_kg_cents)->toBe(450)
        ->and($shipment->fresh()->computed_charge_cents)->toBe(4500);
});

test('a shipment cannot sit in two active batches at once', function () {
    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->direct->id, 'rate_per_kg_cents' => 450,
    ]);

    $shipment = shipmentWeighing(10);
    $this->service->assign($shipment, batchOn($this->direct));
    $this->service->assign($shipment, batchOn($this->direct));
})->throws(DomainException::class);

test('the operator sets the final charge and the difference is derived', function () {
    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->direct->id, 'rate_per_kg_cents' => 450,
    ]);

    $shipment = shipmentWeighing(12.5);
    $this->service->assign($shipment, batchOn($this->direct));

    // Computed $56.25; the operator bills $56.00 (D-020, option B).
    $shipment->setFinalCharge(5600);
    $shipment->refresh();

    expect($shipment->final_charge_cents)->toBe(5600)
        ->and($shipment->computed_charge_cents)->toBe(5625)
        ->and($shipment->roundingAdjustmentCents())->toBe(-25);
});

test('the final charge defaults to the computed charge until it is set', function () {
    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->direct->id, 'rate_per_kg_cents' => 450,
    ]);

    $shipment = shipmentWeighing(10);
    $this->service->assign($shipment, batchOn($this->direct));

    expect($shipment->fresh()->final_charge_cents)->toBe(4500)
        ->and($shipment->fresh()->roundingAdjustmentCents())->toBe(0);
});

test('the operator may round the charge up as well as down', function () {
    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->direct->id, 'rate_per_kg_cents' => 450,
    ]);

    $shipment = shipmentWeighing(8.3);
    $this->service->assign($shipment, batchOn($this->direct));

    expect($shipment->fresh()->computed_charge_cents)->toBe(3735);

    $shipment->setFinalCharge(3800);

    expect($shipment->fresh()->roundingAdjustmentCents())->toBe(65);
});

test('a negative final charge is rejected', function () {
    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->direct->id, 'rate_per_kg_cents' => 450,
    ]);

    $shipment = shipmentWeighing(10);
    $this->service->assign($shipment, batchOn($this->direct));
    $shipment->setFinalCharge(-100);
})->throws(InvalidArgumentException::class);

test('awkward weights still price to an exact cent', function (float $kg, int $rate, int $expected) {
    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->direct->id, 'rate_per_kg_cents' => $rate,
    ]);

    $shipment = shipmentWeighing($kg);
    $this->service->assign($shipment, batchOn($this->direct));

    expect($shipment->fresh()->computed_charge_cents)->toBe($expected);
})->with([
    'a third of a kilo' => [0.3333, 450, 150],   // 0.3333 × 450 = 149.985 -> 150
    'one tenth' => [0.1, 450, 45],
    'the float trap' => [0.7, 300, 210],      // 0.7 × 3 is 2.0999... in binary float
    'a heavy shipment' => [1000.0, 375, 375000],
]);

test('a batch keeps its cost in cents, untouched by customer rounding', function () {
    $batch = batchOn($this->direct);
    $batch->update(['cost_per_kg_cents' => 287]);

    // D-008 and D-020 both keep batch cost exact: only the customer charge is
    // ever adjusted by hand.
    expect($batch->fresh()->cost_per_kg_cents)->toBe(287);
});

test('a new batch starts open', function () {
    expect(batchOn($this->direct)->status)->toBe(BatchStatus::Open);
});
