<?php

use App\Enums\PackageStatus;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\Warehouse;

/**
 * A missing or damaged package does not reduce the charge by itself (D-023).
 *
 * These tests exist because this rule was previously unguarded. The predicate
 * that decides it was changed for an unrelated reason and the whole suite
 * stayed green, because nothing asserted what a flagged package does to the
 * amount a customer owes.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    Route::create([
        'name' => 'Dubai → Syria (direct)',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $damascus->id,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);
});

function shipmentOfThreePackages(): Shipment
{
    $shipment = Shipment::create([
        'customer_id' => test()->customer->id,
        'recipient_name' => 'سامي',
        'recipient_phone' => '+9613000001',
    ]);

    foreach ([10.0, 10.0, 10.0] as $kg) {
        $shipment->packages()->create(['weight_kg' => $kg]);
    }

    $shipment->recalculateTotalWeight();

    return $shipment->fresh();
}

test('a missing package stays in the billable weight', function () {
    $shipment = shipmentOfThreePackages();
    expect((float) $shipment->total_weight_kg)->toBe(30.0);

    $shipment->packages()->first()->forceFill(['status' => PackageStatus::Missing])->save();
    $shipment->recalculateTotalWeight();

    // Still 30. Losing a package is an operational failure, not a discount the
    // system grants on its own — an administrator decides what the customer
    // owes and that decision is recorded (D-023).
    expect((float) $shipment->fresh()->total_weight_kg)->toBe(30.0);
});

test('a damaged package stays in the billable weight', function () {
    $shipment = shipmentOfThreePackages();

    $shipment->packages()->first()->forceFill(['status' => PackageStatus::Damaged])->save();
    $shipment->recalculateTotalWeight();

    expect((float) $shipment->fresh()->total_weight_kg)->toBe(30.0);
});

test('only an explicit cancellation removes a package from the billable weight', function () {
    $shipment = shipmentOfThreePackages();

    $shipment->packages()->first()->forceFill(['status' => PackageStatus::Cancelled])->save();
    $shipment->recalculateTotalWeight();

    // Cancelling is a deliberate act on a specific package, so it is the one
    // status that may move the total.
    expect((float) $shipment->fresh()->total_weight_kg)->toBe(20.0);
});

test('a missing package counts as active, which is what holds the collection gate shut', function () {
    // D-015 gates collection on active packages. If a missing package were
    // inactive it would drop out of that check and let an incomplete shipment
    // be released — the failure this pairing prevents.
    expect(PackageStatus::Missing->isActive())->toBeTrue()
        ->and(PackageStatus::Damaged->isActive())->toBeTrue()
        ->and(PackageStatus::Cancelled->isActive())->toBeFalse();
});
