<?php

use App\Enums\BatchStatus;
use App\Enums\PackageStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PackageJourneyProjection;
use App\Services\PackageJourneyService;

beforeEach(function () {
    $this->origin = Warehouse::create(['name' => 'دبي', 'location' => 'الإمارات']);
    $this->transit = Warehouse::create(['name' => 'بيروت', 'location' => 'لبنان']);
    $this->destination = Warehouse::create(['name' => 'دمشق', 'location' => 'سوريا']);

    $this->route = Route::create([
        'name' => 'دبي ← بيروت ← دمشق',
        'origin_warehouse_id' => $this->origin->id,
        'transit_warehouse_id' => $this->transit->id,
        'destination_warehouse_id' => $this->destination->id,
        'origin_airport_name' => 'مطار دبي',
        'destination_airport_name' => 'مطار بيروت',
        'delivery_office_name' => 'مكتب دمشق',
    ]);

    $this->batch = Batch::create([
        'route_id' => $this->route->id,
        'status' => BatchStatus::InTransit,
    ]);

    $this->customer = Customer::create(['name' => 'عميل اختبار الفحص', 'phone' => '+971500099901']);

    $this->admin = User::create([
        'name' => 'مدير الفحص',
        'email' => 'audit-admin@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::Administrator,
    ]);

    $this->originEmployee = User::create([
        'name' => 'موظف دبي الفحص',
        'email' => 'audit-origin@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $this->origin->id,
    ]);
});

function auditShipment(Batch $batch, Customer $customer, array $statuses): Shipment
{
    $shipment = Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'المستلم',
        'recipient_phone' => '+963900099901',
        'status' => ShipmentStatus::Pending,
    ]);
    $shipment->forceFill(['batch_id' => $batch->id])->save();

    foreach ($statuses as $index => $status) {
        $shipment->packages()->create([
            'weight_kg' => $index + 1,
            'status' => $status,
        ]);
    }

    return $shipment->fresh();
}

// === Fix 1: Default package status is Created, not a dead-end orphan ===

test('a new package defaults to Created status', function () {
    $shipment = auditShipment($this->batch, $this->customer, []);
    $package = $shipment->packages()->create(['weight_kg' => 5]);

    expect($package->status)->toBe(PackageStatus::Created)
        ->and($package->status->journeyPosition())->toBeNull();
});

test('Created packages are not part of journeySteps', function () {
    expect(PackageStatus::Created->journeyPosition())->toBeNull();
});

test('the removed Received and Arrived statuses no longer exist in PackageStatus', function () {
    $statusValues = collect(PackageStatus::cases())->map->value->all();

    expect($statusValues)->not->toContain('received')
        ->and($statusValues)->not->toContain('arrived');
});

test('all seven journey steps are intact after cleanup', function () {
    $steps = PackageStatus::journeySteps();

    expect($steps)->toHaveCount(7)
        ->and($steps[0])->toBe(PackageStatus::ReceivedOrigin)
        ->and($steps[1])->toBe(PackageStatus::ArrivedOriginAirport)
        ->and($steps[2])->toBe(PackageStatus::InTransit)
        ->and($steps[3])->toBe(PackageStatus::ArrivedTransit)
        ->and($steps[4])->toBe(PackageStatus::DepartedTransit)
        ->and($steps[5])->toBe(PackageStatus::ArrivedDestination)
        ->and($steps[6])->toBe(PackageStatus::Collected);
});

// === Fix 2: A Created package can be advanced to ReceivedOrigin ===

test('a Created package advances to ReceivedOrigin on first progress', function () {
    $shipment = auditShipment($this->batch, $this->customer, [PackageStatus::Created]);
    $package = $shipment->packages->first();

    $service = app(PackageJourneyService::class);
    $service->advance($shipment, [$package->id], $this->originEmployee);

    expect($package->fresh()->status)->toBe(PackageStatus::ReceivedOrigin);
});

// === Fix 3: recalculateOperationalStatus handles ReceivedOrigin ===

test('shipment status updates to Assigned when all packages are at ReceivedOrigin', function () {
    $shipment = auditShipment($this->batch, $this->customer, [
        PackageStatus::ReceivedOrigin,
        PackageStatus::ReceivedOrigin,
    ]);

    $shipment->recalculateOperationalStatus();

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::Assigned);
});

test('shipment status updates to Assigned when some packages are Created and some at ReceivedOrigin', function () {
    $shipment = auditShipment($this->batch, $this->customer, [
        PackageStatus::Created,
        PackageStatus::ReceivedOrigin,
    ]);

    $shipment->recalculateOperationalStatus();

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::Assigned);
});

test('shipment status does not change when all packages are still Created', function () {
    $shipment = auditShipment($this->batch, $this->customer, [
        PackageStatus::Created,
        PackageStatus::Created,
    ]);

    $shipment->recalculateOperationalStatus();

    // No ReceivedOrigin packages, no movement — status stays unchanged
    expect($shipment->fresh()->status)->toBe(ShipmentStatus::Pending);
});

// === Fix 4: Journey projection counts packages correctly ===

test('journey projection counts only in-journey packages excluding Created', function () {
    $shipment = auditShipment($this->batch, $this->customer, [
        PackageStatus::Created,
        PackageStatus::ReceivedOrigin,
    ]);

    $projection = app(PackageJourneyProjection::class)->forShipment($shipment);

    // Only ReceivedOrigin is in-journey; Created is excluded from projection
    expect($projection['package_count'])->toBe(1)
        ->and($projection['steps'][0]['current_count'])->toBe(1);
});

test('journey projection completed_count requires all packages past the step', function () {
    $shipment = auditShipment($this->batch, $this->customer, [
        PackageStatus::ReceivedOrigin,
        PackageStatus::ArrivedOriginAirport,
    ]);

    $projection = app(PackageJourneyProjection::class)->forShipment($shipment);

    // Step 0 (ReceivedOrigin): 1 package current, 1 package completed (past it)
    expect($projection['steps'][0]['current_count'])->toBe(1)
        ->and($projection['steps'][0]['completed_count'])->toBe(1);

    // Step 1 (ArrivedOriginAirport): 1 package current, 0 completed
    expect($projection['steps'][1]['current_count'])->toBe(1)
        ->and($projection['steps'][1]['completed_count'])->toBe(0);
});

// === Full journey walk: Created → Collected ===

test('a package walks through all 7 steps from Created to Collected', function () {
    $shipment = auditShipment($this->batch, $this->customer, [PackageStatus::Created]);
    $package = $shipment->packages->first();
    $service = app(PackageJourneyService::class);

    $expectedSteps = PackageStatus::journeySteps();

    foreach ($expectedSteps as $expectedStatus) {
        $service->advance($shipment->fresh(), [$package->id], $this->admin);
        expect($package->fresh()->status)->toBe($expectedStatus);
    }
});
