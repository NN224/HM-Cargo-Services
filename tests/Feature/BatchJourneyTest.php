<?php

use App\Enums\BatchStatus;
use App\Enums\PackageStatus;
use App\Enums\ShipmentStatus;
use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PackageJourneyService;

beforeEach(function () {
    $this->originWh = Warehouse::create(['name' => 'مستودع دبي', 'location' => 'الإمارات']);
    $this->destWh = Warehouse::create(['name' => 'مستودع بيروت', 'location' => 'لبنان']);

    $this->route = Route::create([
        'name' => 'Dubai to Beirut',
        'origin_warehouse_id' => $this->originWh->id,
        'destination_warehouse_id' => $this->destWh->id,
    ]);

    $this->customer = Customer::create([
        'name' => 'العميل',
        'phone' => '+971500000000',
    ]);

    $this->admin = User::factory()->create([
        'warehouse_id' => $this->originWh->id,
        'role' => 'administrator',
    ]);
});

test('can advance all shipments in a batch collectively', function () {
    $batch = Batch::create([
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
    ]);

    $shipment1 = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'المستلم 1',
        'recipient_phone' => '+963900000911',
        'status' => ShipmentStatus::Draft,
    ]);
    $shipment1->forceFill(['batch_id' => $batch->id])->save();

    $shipment2 = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'المستلم 2',
        'recipient_phone' => '+963900000912',
        'status' => ShipmentStatus::Draft,
    ]);
    $shipment2->forceFill(['batch_id' => $batch->id])->save();

    $pkg1 = $shipment1->packages()->create(['weight_kg' => 10, 'status' => PackageStatus::ReceivedOrigin]);
    $pkg2 = $shipment2->packages()->create(['weight_kg' => 15, 'status' => PackageStatus::ReceivedOrigin]);

    $service = app(PackageJourneyService::class);
    $service->advanceBatch($batch, $this->admin);

    expect($pkg1->fresh()->status)->toBe(PackageStatus::ArrivedOriginAirport);
    expect($pkg2->fresh()->status)->toBe(PackageStatus::ArrivedOriginAirport);
});

test('can advance newly created packages in a batch to the origin warehouse stage', function () {
    $batch = Batch::create([
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
    ]);

    $shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'المستلم',
        'recipient_phone' => '+963900000913',
        'status' => ShipmentStatus::Draft,
    ]);
    $shipment->forceFill(['batch_id' => $batch->id])->save();

    $pkg = $shipment->packages()->create(['weight_kg' => 10, 'status' => PackageStatus::Created]);

    $service = app(PackageJourneyService::class);
    $service->advanceBatch($batch, $this->admin);

    expect($pkg->fresh()->status)->toBe(PackageStatus::ReceivedOrigin);
});

test('can delay all shipments in a batch collectively', function () {
    $batch = Batch::create([
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
    ]);

    $shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'المستلم',
        'recipient_phone' => '+963900000911',
        'status' => ShipmentStatus::Draft,
    ]);
    $shipment->forceFill(['batch_id' => $batch->id])->save();

    $pkg = $shipment->packages()->create(['weight_kg' => 10, 'status' => PackageStatus::ReceivedOrigin]);

    $service = app(PackageJourneyService::class);
    $service->delayBatch($batch, $this->admin, 'سوء الأحوال الجوية', true);

    expect($pkg->fresh()->is_delayed)->toBeTrue();
    expect($pkg->fresh()->delay_reason)->toBe('سوء الأحوال الجوية');
});

test('administrator can correct all packages in a batch to a target status', function () {
    $batch = Batch::create([
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
    ]);

    $shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'المستلم',
        'recipient_phone' => '+963900000911',
        'status' => ShipmentStatus::Draft,
    ]);
    $shipment->forceFill(['batch_id' => $batch->id])->save();

    $pkg1 = $shipment->packages()->create(['weight_kg' => 5, 'status' => PackageStatus::ReceivedOrigin]);
    $pkg2 = $shipment->packages()->create(['weight_kg' => 8, 'status' => PackageStatus::ArrivedOriginAirport]);

    $service = app(PackageJourneyService::class);
    $service->correctBatch($batch, PackageStatus::ArrivedDestination, $this->admin, 'تصحيح إداري', false);

    expect($pkg1->fresh()->status)->toBe(PackageStatus::ArrivedDestination);
    expect($pkg2->fresh()->status)->toBe(PackageStatus::ArrivedDestination);

    expect(AuditLog::where('action', 'package_journey_corrected')->count())->toBe(2);
});

test('administrator can correct newly created packages in a batch to a target journey status', function () {
    $batch = Batch::create([
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
    ]);

    $shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'المستلم',
        'recipient_phone' => '+963900000914',
        'status' => ShipmentStatus::Draft,
    ]);
    $shipment->forceFill(['batch_id' => $batch->id])->save();

    $pkg = $shipment->packages()->create(['weight_kg' => 5, 'status' => PackageStatus::Created]);

    $service = app(PackageJourneyService::class);
    $service->correctBatch($batch, PackageStatus::InTransit, $this->admin, 'تصحيح إداري', false);

    expect($pkg->fresh()->status)->toBe(PackageStatus::InTransit)
        ->and(AuditLog::where('action', 'package_journey_corrected')->count())->toBe(1);
});

test('correctBatch is refused for non-administrator', function () {
    $employee = User::factory()->create([
        'warehouse_id' => $this->originWh->id,
        'role' => 'warehouse_employee',
    ]);

    $batch = Batch::create([
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
    ]);

    $shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'المستلم',
        'recipient_phone' => '+963900000911',
        'status' => ShipmentStatus::Draft,
    ]);
    $shipment->forceFill(['batch_id' => $batch->id])->save();
    $shipment->packages()->create(['weight_kg' => 5, 'status' => PackageStatus::ReceivedOrigin]);

    $service = app(PackageJourneyService::class);

    expect(fn () => $service->correctBatch(
        $batch,
        PackageStatus::ArrivedDestination,
        $employee,
        'غير مسموح',
        false,
    ))->toThrow(DomainException::class, 'صلاحية المدير');
});
