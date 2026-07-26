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
    $this->customer = Customer::create(['name' => 'عميل التأخير', 'phone' => '+971500000911']);
    $this->originEmployee = User::create([
        'name' => 'موظف دبي',
        'email' => 'delay-origin@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $this->origin->id,
    ]);
});

function journeyDelayShipment(Batch $batch, Customer $customer, array $statuses): Shipment
{
    $shipment = Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'المستلم',
        'recipient_phone' => '+963900000911',
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

test('an employee delays selected packages with a private reason', function () {
    $shipment = journeyDelayShipment($this->batch, $this->customer, [PackageStatus::ArrivedOriginAirport]);

    app(PackageJourneyService::class)->delay(
        $shipment,
        $shipment->packages->modelKeys(),
        $this->originEmployee,
        'تأخر التحميل',
        false,
    );

    $package = $shipment->packages->first()->fresh();

    expect($package->is_delayed)->toBeTrue()
        ->and($package->delay_reason)->toBe('تأخر التحميل')
        ->and($package->delay_reason_is_public)->toBeFalse();

    $this->assertDatabaseHas('package_status_events', [
        'package_id' => $package->id,
        'previous_status' => PackageStatus::ArrivedOriginAirport->value,
        'status' => PackageStatus::ArrivedOriginAirport->value,
        'event_kind' => 'delay',
        'private_reason' => 'تأخر التحميل',
        'public_reason' => null,
    ]);
});

test('a published delay stores the public reason separately', function () {
    $shipment = journeyDelayShipment($this->batch, $this->customer, [PackageStatus::ArrivedOriginAirport]);
    $package = $shipment->packages->first();

    app(PackageJourneyService::class)->delay(
        $shipment,
        [$package->id],
        $this->originEmployee,
        'انتظار تحميل الطائرة',
        true,
    );

    $this->assertDatabaseHas('package_status_events', [
        'package_id' => $package->id,
        'event_kind' => 'delay',
        'private_reason' => null,
        'public_reason' => 'انتظار تحميل الطائرة',
    ]);
});

test('forward progress clears the current delay but retains its event', function () {
    $shipment = journeyDelayShipment($this->batch, $this->customer, [PackageStatus::ArrivedOriginAirport]);
    $package = $shipment->packages->first();
    $service = app(PackageJourneyService::class);

    $service->delay(
        $shipment,
        [$package->id],
        $this->originEmployee,
        'تأخر التحميل',
        true,
    );
    $service->advance($shipment, [$package->id], $this->originEmployee);

    expect($package->fresh()->is_delayed)->toBeFalse()
        ->and($package->fresh()->delay_reason)->toBeNull()
        ->and(DB::table('package_status_events')
            ->where('package_id', $package->id)
            ->where('event_kind', 'delay')
            ->exists())->toBeTrue();
});

test('delay requires a non-empty reason', function () {
    $shipment = journeyDelayShipment($this->batch, $this->customer, [PackageStatus::ArrivedOriginAirport]);

    expect(fn () => app(PackageJourneyService::class)->delay(
        $shipment,
        $shipment->packages->modelKeys(),
        $this->originEmployee,
        '   ',
        false,
    ))->toThrow(DomainException::class, 'السبب');
});
