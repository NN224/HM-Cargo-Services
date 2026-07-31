<?php

use App\Enums\BatchStatus;
use App\Enums\PackageStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PackageJourneyService;

beforeEach(function () {
    $this->origin = Warehouse::create(['name' => 'دبي', 'location' => 'الإمارات']);
    $this->destination = Warehouse::create(['name' => 'دمشق', 'location' => 'سوريا']);
    $this->route = Route::create([
        'name' => 'دبي ← دمشق مباشر',
        'origin_warehouse_id' => $this->origin->id,
        'destination_warehouse_id' => $this->destination->id,
        'origin_airport_name' => 'مطار دبي',
        'destination_airport_name' => 'مطار دمشق',
        'delivery_office_name' => 'مكتب دمشق',
    ]);
    $this->batch = Batch::create(['route_id' => $this->route->id, 'status' => BatchStatus::InTransit]);
    $this->customer = Customer::create(['name' => 'عميل التصحيح', 'phone' => '+971500000912']);
    $this->employee = User::create([
        'name' => 'موظف دبي',
        'email' => 'correction-origin@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $this->origin->id,
    ]);
    $this->admin = User::create([
        'name' => 'مدير التصحيح',
        'email' => 'correction-admin@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);
});

function journeyCorrectionShipment(Batch $batch, Customer $customer, array $statuses): Shipment
{
    $shipment = Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'المستلم',
        'recipient_phone' => '+963900000912',
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

test('an employee cannot correct a package backward', function () {
    $shipment = journeyCorrectionShipment($this->batch, $this->customer, [PackageStatus::ArrivedTransit]);

    expect(fn () => app(PackageJourneyService::class)->correct(
        $shipment,
        $shipment->packages->modelKeys(),
        PackageStatus::ArrivedOriginAirport,
        $this->employee,
        'تصحيح مرحلة',
        false,
    ))->toThrow(DomainException::class, 'المدير');
});

test('an administrator can move selected packages backward with a private reason', function () {
    $shipment = journeyCorrectionShipment($this->batch, $this->customer, [
        PackageStatus::ArrivedTransit,
        PackageStatus::ArrivedTransit,
    ]);
    $selected = $shipment->packages->first();

    app(PackageJourneyService::class)->correct(
        $shipment,
        [$selected->id],
        PackageStatus::ArrivedOriginAirport,
        $this->admin,
        'أدخلت المرحلة بالخطأ',
        false,
    );

    expect($selected->fresh()->status)->toBe(PackageStatus::ArrivedOriginAirport)
        ->and($shipment->packages->last()->fresh()->status)->toBe(PackageStatus::ArrivedTransit)
        ->and(AuditLog::where('action', 'package_journey_corrected')->count())->toBe(1);

    $this->assertDatabaseHas('package_status_events', [
        'package_id' => $selected->id,
        'previous_status' => PackageStatus::ArrivedTransit->value,
        'status' => PackageStatus::ArrivedOriginAirport->value,
        'event_kind' => 'correction',
        'private_reason' => 'أدخلت المرحلة بالخطأ',
        'public_reason' => null,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $this->admin->id,
        'action' => 'package_journey_corrected',
        'auditable_type' => Package::class,
        'auditable_id' => $selected->id,
        'reason' => 'أدخلت المرحلة بالخطأ',
    ]);
});

test('an administrator correction requires a non-empty reason', function () {
    $shipment = journeyCorrectionShipment($this->batch, $this->customer, [PackageStatus::ArrivedTransit]);

    expect(fn () => app(PackageJourneyService::class)->correct(
        $shipment,
        $shipment->packages->modelKeys(),
        PackageStatus::ArrivedOriginAirport,
        $this->admin,
        '',
        false,
    ))->toThrow(DomainException::class, 'السبب');
});

test('a published administrator correction writes only a public reason event', function () {
    $shipment = journeyCorrectionShipment($this->batch, $this->customer, [PackageStatus::ArrivedTransit]);
    $package = $shipment->packages->first();

    app(PackageJourneyService::class)->correct(
        $shipment,
        [$package->id],
        PackageStatus::ArrivedOriginAirport,
        $this->admin,
        'تصحيح ظاهر للعميل',
        true,
    );

    $this->assertDatabaseHas('package_status_events', [
        'package_id' => $package->id,
        'event_kind' => 'correction',
        'private_reason' => null,
        'public_reason' => 'تصحيح ظاهر للعميل',
    ]);
});

test('administrator correction rejects non-journey target states', function () {
    $shipment = journeyCorrectionShipment($this->batch, $this->customer, [PackageStatus::ArrivedTransit]);

    expect(fn () => app(PackageJourneyService::class)->correct(
        $shipment,
        $shipment->packages->modelKeys(),
        PackageStatus::Damaged,
        $this->admin,
        'تصحيح غير صالح',
        false,
    ))->toThrow(DomainException::class, 'مرحلة');
});
