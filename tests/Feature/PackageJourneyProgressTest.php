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
use App\Services\PackageJourneyService;
use Illuminate\Support\Facades\DB;

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

    $this->customer = Customer::create(['name' => 'عميل الرحلة', 'phone' => '+971500000901']);

    $this->originEmployee = User::create([
        'name' => 'موظف دبي',
        'email' => 'journey-origin@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $this->origin->id,
    ]);

    $this->transitEmployee = User::create([
        'name' => 'موظف بيروت',
        'email' => 'journey-transit@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $this->transit->id,
    ]);

    $this->unrelatedEmployee = User::create([
        'name' => 'موظف خارج المسار',
        'email' => 'journey-other@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => Warehouse::create(['name' => 'عمان', 'location' => 'الأردن'])->id,
    ]);
});

function journeyProgressShipment(Batch $batch, Customer $customer, array $statuses): Shipment
{
    $shipment = Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'المستلم',
        'recipient_phone' => '+963900000901',
        'status' => ShipmentStatus::InTransit,
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

test('an employee advances selected packages by exactly one step', function () {
    $shipment = journeyProgressShipment($this->batch, $this->customer, [
        PackageStatus::ReceivedOrigin,
        PackageStatus::ReceivedOrigin,
    ]);
    $selected = $shipment->packages->first();

    $advanced = app(PackageJourneyService::class)->advance(
        $shipment,
        [$selected->id],
        $this->originEmployee,
    );

    expect($selected->fresh()->status)->toBe(PackageStatus::ArrivedOriginAirport)
        ->and($shipment->packages->last()->fresh()->status)->toBe(PackageStatus::ReceivedOrigin)
        ->and($advanced->status)->toBe(ShipmentStatus::PartialAtTransit);

    $this->assertDatabaseHas('package_status_events', [
        'package_id' => $selected->id,
        'previous_status' => PackageStatus::ReceivedOrigin->value,
        'status' => PackageStatus::ArrivedOriginAirport->value,
        'event_kind' => 'progress',
        'warehouse_id' => $this->origin->id,
        'user_id' => $this->originEmployee->id,
    ]);
});

test('all selected packages at the origin airport map the shipment to in transit', function () {
    $shipment = journeyProgressShipment($this->batch, $this->customer, [
        PackageStatus::ReceivedOrigin,
        PackageStatus::ReceivedOrigin,
    ]);

    $advanced = app(PackageJourneyService::class)->advance(
        $shipment,
        $shipment->packages->modelKeys(),
        $this->originEmployee,
    );

    expect($advanced->status)->toBe(ShipmentStatus::InTransit)
        ->and($shipment->packages()->where('status', PackageStatus::ArrivedOriginAirport->value)->count())->toBe(2);
});

test('one ineligible selected package rolls back the complete operation', function () {
    $shipment = journeyProgressShipment($this->batch, $this->customer, [
        PackageStatus::ReceivedOrigin,
        PackageStatus::Damaged,
    ]);

    expect(fn () => app(PackageJourneyService::class)->advance(
        $shipment,
        $shipment->packages->modelKeys(),
        $this->originEmployee,
    ))->toThrow(DomainException::class);

    expect($shipment->packages()->where('status', PackageStatus::ReceivedOrigin->value)->count())->toBe(1)
        ->and($shipment->packages()->where('status', PackageStatus::Damaged->value)->count())->toBe(1)
        ->and(DB::table('package_status_events')->count())->toBe(0);
});

test('an origin employee cannot advance a package into the transit checkpoint', function () {
    $shipment = journeyProgressShipment($this->batch, $this->customer, [
        PackageStatus::ArrivedOriginAirport,
    ]);

    expect(fn () => app(PackageJourneyService::class)->advance(
        $shipment,
        $shipment->packages->modelKeys(),
        $this->originEmployee,
    ))->toThrow(DomainException::class, 'غير مخوّل');

    expect($shipment->packages->first()->fresh()->status)->toBe(PackageStatus::ArrivedOriginAirport);
});

test('an unrelated warehouse employee cannot advance route packages', function () {
    $shipment = journeyProgressShipment($this->batch, $this->customer, [
        PackageStatus::ReceivedOrigin,
    ]);

    expect(fn () => app(PackageJourneyService::class)->advance(
        $shipment,
        $shipment->packages->modelKeys(),
        $this->unrelatedEmployee,
    ))->toThrow(DomainException::class, 'غير مخوّل');
});

test('an administrator may advance a package without a warehouse assignment', function () {
    $admin = User::create([
        'name' => 'مدير الرحلة',
        'email' => 'journey-admin@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);
    $shipment = journeyProgressShipment($this->batch, $this->customer, [
        PackageStatus::ArrivedOriginAirport,
    ]);

    app(PackageJourneyService::class)->advance(
        $shipment,
        $shipment->packages->modelKeys(),
        $admin,
    );

    expect($shipment->packages->first()->fresh()->status)->toBe(PackageStatus::ArrivedTransit);
});

test('selected package ids must belong exactly to the shipment', function () {
    $shipment = journeyProgressShipment($this->batch, $this->customer, [
        PackageStatus::ReceivedOrigin,
    ]);
    $other = journeyProgressShipment($this->batch, $this->customer, [
        PackageStatus::ReceivedOrigin,
    ]);

    expect(fn () => app(PackageJourneyService::class)->advance(
        $shipment,
        [$shipment->packages->first()->id, $other->packages->first()->id],
        $this->originEmployee,
    ))->toThrow(DomainException::class, 'الطرود المحددة');
});
