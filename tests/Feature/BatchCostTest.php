<?php

use App\Enums\BatchStatus;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\Warehouse;

/**
 * Batch cost and profit arithmetic.
 *
 * These figures decide what the company believes it earned, so they are held
 * to the same standard as the customer charge: integer cents, decimal
 * multiplication, and a refusal to guess when a rate is missing.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->route = Route::create([
        'name' => 'Dubai → Syria (direct)',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $damascus->id,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);
});

function batchCarrying(array $weights, ?int $costPerKgCents): Batch
{
    $batch = Batch::create([
        'reference' => 'BCH-TEST-'.uniqid(),
        'route_id' => test()->route->id,
        'status' => BatchStatus::Open,
        'cost_per_kg_cents' => $costPerKgCents,
    ]);

    foreach ($weights as $kg) {
        $shipment = Shipment::create([
            'customer_id' => test()->customer->id,
            'recipient_name' => 'سامي',
            'recipient_phone' => '+9613000001',
        ]);
        $shipment->packages()->create(['weight_kg' => $kg]);
        $shipment->forceFill(['batch_id' => $batch->id])->save();
    }

    return $batch->fresh();
}

test('a batch with no cost rate reports its cost as unavailable, not as zero', function () {
    $batch = batchCarrying([10.0], costPerKgCents: null);

    // Zero would read as "this batch was free" and inflate profit by the whole
    // revenue. pricing-payments.md is explicit: a missing rate is not zero.
    expect($batch->costCents())->toBeNull();
});

test('profit is unavailable while the cost is unknown', function () {
    $batch = batchCarrying([10.0], costPerKgCents: null);

    expect($batch->profitCents())->toBeNull();
});

test('cost is the exact weight multiplied by the rate, to the cent', function () {
    $batch = batchCarrying([10.0], costPerKgCents: 250);

    expect($batch->costCents())->toBe(2500);
});

test('cost is exact at weights that float multiplication represents poorly', function () {
    // 0.7 × 300 evaluates to 209.999... in binary floating point. A bare (int)
    // cast would truncate that to 209; decimal multiplication gives 210.
    // This guards the result rather than the technique — it passes either way,
    // and exists so a future refactor cannot quietly reintroduce truncation.
    $batch = batchCarrying([0.7], costPerKgCents: 300);

    expect($batch->costCents())->toBe(210);
});

test('cost sums every shipment in the batch', function () {
    $batch = batchCarrying([1.25, 2.75], costPerKgCents: 400);

    // (1.25 + 2.75) × 400 = 1600
    expect($batch->costCents())->toBe(1600);
});

test('profit is revenue minus exact cost once a rate exists', function () {
    $batch = batchCarrying([10.0], costPerKgCents: 250);

    $batch->shipments()->first()->forceFill(['final_charge_cents' => 4000])->save();

    expect($batch->fresh()->profitCents())->toBe(1500);
});
