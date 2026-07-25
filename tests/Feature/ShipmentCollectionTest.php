<?php

use App\Enums\PackageStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ShipmentCollectionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->origin = Warehouse::create(['name' => 'دبي', 'location' => 'الإمارات']);
    $this->destination = Warehouse::create(['name' => 'دمشق', 'location' => 'سوريا']);
    $this->route = Route::create([
        'name' => 'دبي ← دمشق',
        'origin_warehouse_id' => $this->origin->id,
        'destination_warehouse_id' => $this->destination->id,
    ]);
    $this->batch = Batch::create(['route_id' => $this->route->id]);
    $this->customer = Customer::create(['name' => 'عميل التسليم', 'phone' => '+971500000302']);
    $this->employee = User::create([
        'name' => 'موظف التسليم',
        'email' => 'collector@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $this->destination->id,
    ]);
});

function collectionTestShipment(Batch $batch, Customer $customer, array $statuses): Shipment
{
    $shipment = Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'المستلم',
        'recipient_phone' => '+963900000302',
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

test('collection is refused while any active package has not arrived', function () {
    $shipment = collectionTestShipment($this->batch, $this->customer, [
        PackageStatus::ArrivedDestination,
        PackageStatus::InTransit,
    ]);

    expect(fn () => app(ShipmentCollectionService::class)->collect(
        $shipment,
        $this->destination,
        $this->employee,
    ))->toThrow(DomainException::class, 'لا يمكن تسليم الشحنة');

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::InTransit)
        ->and($shipment->packages()->where('status', PackageStatus::Collected->value)->count())->toBe(0);
});

test('all active packages and the shipment are collected in one controlled transaction', function () {
    $shipment = collectionTestShipment($this->batch, $this->customer, [
        PackageStatus::ArrivedDestination,
        PackageStatus::ArrivedDestination,
    ]);

    $collected = app(ShipmentCollectionService::class)->collect(
        $shipment,
        $this->destination,
        $this->employee,
    );

    expect($collected->status)->toBe(ShipmentStatus::Collected)
        ->and($shipment->packages()->where('status', PackageStatus::Collected->value)->count())->toBe(2)
        ->and(DB::table('package_status_events')->where('source', 'collection')->count())->toBe(2);
});

test('cancelled packages do not create a partial pickup', function () {
    $shipment = collectionTestShipment($this->batch, $this->customer, [
        PackageStatus::ArrivedDestination,
        PackageStatus::Cancelled,
    ]);

    app(ShipmentCollectionService::class)->collect(
        $shipment,
        $this->destination,
        $this->employee,
    );

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::Collected)
        ->and($shipment->packages()->where('status', PackageStatus::Collected->value)->count())->toBe(1)
        ->and($shipment->packages()->where('status', PackageStatus::Cancelled->value)->count())->toBe(1);
});

test('missing and damaged packages remain active and block collection', function (PackageStatus $status) {
    $shipment = collectionTestShipment($this->batch, $this->customer, [
        PackageStatus::ArrivedDestination,
        $status,
    ]);

    app(ShipmentCollectionService::class)->collect(
        $shipment,
        $this->destination,
        $this->employee,
    );
})->with([
    'missing' => PackageStatus::Missing,
    'damaged' => PackageStatus::Damaged,
])->throws(DomainException::class, 'لا يمكن تسليم الشحنة');

test('warehouse policy refuses collection outside the assigned warehouse', function () {
    $shipment = collectionTestShipment($this->batch, $this->customer, [
        PackageStatus::ArrivedDestination,
    ]);

    expect(fn () => app(ShipmentCollectionService::class)->collect(
        $shipment,
        $this->origin,
        $this->employee,
    ))->toThrow(AuthorizationException::class);

    expect($shipment->fresh()->status)->not->toBe(ShipmentStatus::Collected);
});

test('d029 partial collection is refused for non-administrator employees', function () {
    $shipment = collectionTestShipment($this->batch, $this->customer, [
        PackageStatus::ArrivedDestination,
        PackageStatus::InTransit,
    ]);

    expect(fn () => app(ShipmentCollectionService::class)->collectPartially(
        $shipment,
        $this->destination,
        $this->employee,
    ))->toThrow(DomainException::class, 'التسليم الجزئي يحتاج إلى موافقة وبطاقة مدير النظام');
});

test('d029 partial collection succeeds with administrator approval', function () {
    $admin = User::create([
        'name' => 'المدير العام',
        'email' => 'admin_partial@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::Administrator,
        'warehouse_id' => $this->destination->id,
    ]);

    $shipment = collectionTestShipment($this->batch, $this->customer, [
        PackageStatus::ArrivedDestination,
        PackageStatus::InTransit,
    ]);

    $partiallyCollected = app(ShipmentCollectionService::class)->collectPartially(
        $shipment,
        $this->destination,
        $admin,
    );

    expect($partiallyCollected->status)->toBe(ShipmentStatus::PartiallyCollected)
        ->and($shipment->packages()->where('status', PackageStatus::Collected->value)->count())->toBe(1)
        ->and($shipment->packages()->where('status', PackageStatus::InTransit->value)->count())->toBe(1);
});
