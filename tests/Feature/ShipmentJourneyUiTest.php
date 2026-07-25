<?php

use App\Enums\BatchStatus;
use App\Enums\PackageStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Shipments\Pages\ListShipments;
use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

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
    $this->batch = Batch::create([
        'route_id' => $this->route->id,
        'status' => BatchStatus::InTransit,
    ]);
    $this->customer = Customer::create(['name' => 'عميل الواجهة', 'phone' => '+971500000931']);
    $this->employee = User::create([
        'name' => 'موظف دبي',
        'email' => 'journey-ui@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $this->origin->id,
    ]);
});

function journeyUiShipment(Batch $batch, Customer $customer): Shipment
{
    $shipment = Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'المستلم',
        'recipient_phone' => '+963900000931',
        'status' => ShipmentStatus::InTransit,
    ]);
    $shipment->forceFill(['batch_id' => $batch->id])->save();
    $shipment->packages()->create(['weight_kg' => 1, 'status' => PackageStatus::ArrivedOriginAirport]);
    $shipment->packages()->create(['weight_kg' => 2, 'status' => PackageStatus::InTransit]);

    return $shipment->fresh();
}

test('shipment cards show the seven dynamic journey labels without duplicated group heading', function () {
    journeyUiShipment($this->batch, $this->customer);

    Livewire::actingAs($this->employee)
        ->test(ListShipments::class)
        ->assertSee('وصل مستودع دبي', escape: false)
        ->assertSee('وصل مطار دبي', escape: false)
        ->assertSee('غادر مطار دبي', escape: false)
        ->assertSee('وصل مطار دمشق', escape: false)
        ->assertSee('غادر مطار دمشق', escape: false)
        ->assertSee('وصل مكتب دمشق', escape: false)
        ->assertSee('استلمه العميل', escape: false)
        ->assertDontSee('الرحلة: الرحلة:', escape: false);
});

test('employees can open the journey action but cannot perform administrator correction', function () {
    $shipment = journeyUiShipment($this->batch, $this->customer);
    $package = $shipment->packages->first();

    Livewire::actingAs($this->employee)
        ->test(ListShipments::class)
        ->assertTableActionVisible('manageJourney', $shipment->id)
        ->callTableAction('manageJourney', $shipment->id, data: [
            'operation' => 'correct',
            'package_ids' => [$package->id],
            'target_status' => PackageStatus::ReceivedOrigin->value,
            'reason' => 'تصحيح غير مسموح',
            'publish_reason' => false,
        ]);

    expect($package->fresh()->status)->toBe(PackageStatus::ArrivedOriginAirport)
        ->and(AuditLog::count())->toBe(0);
});

test('the journey action requires selecting at least one package', function () {
    $shipment = journeyUiShipment($this->batch, $this->customer);

    Livewire::actingAs($this->employee)
        ->test(ListShipments::class)
        ->callTableAction('manageJourney', $shipment->id, data: [
            'operation' => 'advance',
            'package_ids' => [],
        ])
        ->assertHasTableActionErrors(['package_ids' => 'required']);
});
