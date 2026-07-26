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
        'reference' => 'BATCH-UI-001',
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
        ->assertSee('وصلت مستودع دبي', escape: false)
        ->assertSee('وصلت مطار دبي', escape: false)
        ->assertSee('غادرت مطار دبي', escape: false)
        ->assertSee('وصلت مطار دمشق', escape: false)
        ->assertSee('غادرت مطار دمشق', escape: false)
        ->assertSee('وصلت مكتب دمشق', escape: false)
        ->assertSee('استلمها العميل', escape: false)
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

test('batch selector bar shows only active batches', function () {
    Batch::create([
        'route_id' => $this->route->id,
        'status' => BatchStatus::Completed,
        'reference' => 'BATCH-DONE-001',
    ]);

    journeyUiShipment($this->batch, $this->customer);

    Livewire::actingAs($this->employee)
        ->test(ListShipments::class)
        ->assertSee('BATCH-UI-001')
        ->assertDontSee('الرحلة: BATCH-DONE-001');
});

test('non-administrator cannot see correct operation in batch journey action', function () {
    $batch = Batch::create([
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
        'reference' => 'BATCH-CORR-001',
    ]);
    journeyUiShipment($batch, $this->customer);

    Livewire::actingAs($this->employee)
        ->test(ListShipments::class)
        ->set('managingBatchId', $batch->id)
        ->callAction('manageBatchJourney', data: [
            'operation' => 'correct',
            'target_status' => PackageStatus::ArrivedDestination->value,
            'reason' => 'محاولة غير مسموحة',
        ])
        ->assertHasActionErrors(['operation']);
});

test('administrator can perform batch journey correction via page action', function () {
    $admin = User::factory()->create([
        'warehouse_id' => $this->origin->id,
        'role' => 'administrator',
    ]);

    $batch = Batch::create([
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
        'reference' => 'BATCH-ADMIN-001',
    ]);
    $shipment = journeyUiShipment($batch, $this->customer);

    Livewire::actingAs($admin)
        ->test(ListShipments::class)
        ->set('managingBatchId', $batch->id)
        ->callAction('manageBatchJourney', data: [
            'operation' => 'correct',
            'target_status' => PackageStatus::ArrivedDestination->value,
            'reason' => 'تصحيح إداري اختباري',
            'publish_reason' => false,
        ]);

    $shipment->packages->each(function ($pkg) {
        expect($pkg->fresh()->status)->toBe(PackageStatus::ArrivedDestination);
    });
});

test('selecting a batch filters the table to its shipments only', function () {
    $batch2 = Batch::create([
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
        'reference' => 'BATCH-SELECT-TEST',
    ]);

    $s1 = journeyUiShipment($this->batch, $this->customer);
    $s2 = journeyUiShipment($batch2, $this->customer);

    Livewire::actingAs($this->employee)
        ->test(ListShipments::class)
        ->assertSee($s1->reference)
        ->assertSee($s2->reference)
        ->call('selectBatch', $this->batch->id)
        ->assertSee($s1->reference)
        ->assertDontSee($s2->reference);
});

test('selecting all batches clears the filter', function () {
    $s1 = journeyUiShipment($this->batch, $this->customer);

    Livewire::actingAs($this->employee)
        ->test(ListShipments::class)
        ->call('selectBatch', $this->batch->id)
        ->assertSee($s1->reference)
        ->call('selectBatch', null)
        ->assertSee($s1->reference)
        ->assertSee('جميع الرحلات');
});

test('completed and cancelled batches do not appear in sidebar', function () {
    Batch::create([
        'route_id' => $this->route->id,
        'status' => BatchStatus::Completed,
        'reference' => 'BATCH-DONE',
    ]);
    Batch::create([
        'route_id' => $this->route->id,
        'status' => BatchStatus::Cancelled,
        'reference' => 'BATCH-CXL',
    ]);

    journeyUiShipment($this->batch, $this->customer);

    Livewire::actingAs($this->employee)
        ->test(ListShipments::class)
        ->assertDontSee('BATCH-DONE')
        ->assertDontSee('BATCH-CXL');
});

test('workspace header shows selected batch info', function () {
    $batch = Batch::create([
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
        'reference' => 'BATCH-WS-HEADER',
    ]);
    $s = journeyUiShipment($batch, $this->customer);
    $s->forceFill(['batch_id' => $batch->id, 'total_weight_kg' => 15])->save();

    Livewire::actingAs($this->employee)
        ->test(ListShipments::class)
        ->call('selectBatch', $batch->id)
        ->assertSee('BATCH-WS-HEADER')
        ->assertSee('دبي ← دمشق');
});

test('changing batch selection clears selection when navigating to all', function () {
    $batch2 = Batch::create([
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
        'reference' => 'BATCH-ALT',
    ]);

    journeyUiShipment($this->batch, $this->customer);
    journeyUiShipment($batch2, $this->customer);

    $component = Livewire::actingAs($this->employee)
        ->test(ListShipments::class);

    $component->call('selectBatch', $this->batch->id);
    expect($component->get('selectedBatchId'))->toBe($this->batch->id);

    $component->call('selectBatch', $batch2->id);
    expect($component->get('selectedBatchId'))->toBe($batch2->id);

    $component->call('selectBatch', null);
    expect($component->get('selectedBatchId'))->toBeNull();
});

test('custom shipment card select all toggles visible shipment selection', function () {
    $s1 = journeyUiShipment($this->batch, $this->customer);
    $s2 = journeyUiShipment($this->batch, $this->customer);

    $component = Livewire::actingAs($this->employee)
        ->test(ListShipments::class);

    $component
        ->call('toggleVisibleShipments', [$s1->id, $s2->id])
        ->assertSee('شحنات محددة', escape: false)
        ->assertSee('تحديث', escape: false);

    expect($component->get('selectedShipmentIds'))->toEqualCanonicalizing([$s1->id, $s2->id]);

    $component->call('toggleVisibleShipments', [$s1->id, $s2->id]);

    expect($component->get('selectedShipmentIds'))->toBe([]);
});

test('custom shipment card single selection can be cancelled from the bulk bar', function () {
    $shipment = journeyUiShipment($this->batch, $this->customer);

    $component = Livewire::actingAs($this->employee)
        ->test(ListShipments::class)
        ->assertSee('تحديد الشحنة '.$shipment->reference, escape: false)
        ->call('toggleShipmentSelection', $shipment->id)
        ->assertSee('شحنات محددة', escape: false);

    expect($component->get('selectedShipmentIds'))->toBe([$shipment->id]);

    $component->call('clearShipmentSelection');

    expect($component->get('selectedShipmentIds'))->toBe([]);
});

test('custom shipment bulk update advances selected shipments one journey step', function () {
    $s1 = journeyUiShipment($this->batch, $this->customer);
    $s2 = journeyUiShipment($this->batch, $this->customer);

    $s1->packages()->update(['status' => PackageStatus::ReceivedOrigin]);
    $s2->packages()->update(['status' => PackageStatus::ReceivedOrigin]);

    $component = Livewire::actingAs($this->employee)
        ->test(ListShipments::class)
        ->call('toggleVisibleShipments', [$s1->id, $s2->id])
        ->call('bulkAdvanceSelectedShipments')
        ->assertNotified('تم تحديث الشحنات المحددة');

    expect($component->get('selectedShipmentIds'))->toBe([])
        ->and($s1->packages()->pluck('status')->all())->each->toBe(PackageStatus::ArrivedOriginAirport)
        ->and($s2->packages()->pluck('status')->all())->each->toBe(PackageStatus::ArrivedOriginAirport);
});

test('custom shipment bulk update starts newly created packages at origin warehouse stage', function () {
    $shipment = journeyUiShipment($this->batch, $this->customer);
    $shipment->packages()->update(['status' => PackageStatus::Created]);

    $component = Livewire::actingAs($this->employee)
        ->test(ListShipments::class)
        ->call('toggleShipmentSelection', $shipment->id)
        ->call('bulkAdvanceSelectedShipments')
        ->assertNotified('تم تحديث الشحنات المحددة');

    expect($component->get('selectedShipmentIds'))->toBe([])
        ->and($shipment->packages()->pluck('status')->all())->each->toBe(PackageStatus::ReceivedOrigin);
});

test('administrator can choose a target status for selected shipment bulk update', function () {
    $admin = User::create([
        'name' => 'مدير المسار',
        'email' => 'bulk-admin@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::Administrator,
        'warehouse_id' => $this->origin->id,
    ]);

    $s1 = journeyUiShipment($this->batch, $this->customer);
    $s2 = journeyUiShipment($this->batch, $this->customer);

    $s1->packages()->update(['status' => PackageStatus::ReceivedOrigin]);
    $s2->packages()->update(['status' => PackageStatus::ReceivedOrigin]);

    $component = Livewire::actingAs($admin)
        ->test(ListShipments::class)
        ->call('toggleVisibleShipments', [$s1->id, $s2->id])
        ->assertSee('الحالة الجديدة', escape: false)
        ->assertSee('غادرت مطار دبي', escape: false)
        ->set('bulkTargetStatus', PackageStatus::InTransit->value)
        ->call('bulkAdvanceSelectedShipments')
        ->assertNotified('تم تحديث الشحنات المحددة');

    expect($component->get('selectedShipmentIds'))->toBe([])
        ->and($component->get('bulkTargetStatus'))->toBeNull()
        ->and(AuditLog::where('reason', 'تصحيح جماعي من شاشة الشحنات')->count())->toBe(4)
        ->and($s1->packages()->pluck('status')->all())->each->toBe(PackageStatus::InTransit)
        ->and($s2->packages()->pluck('status')->all())->each->toBe(PackageStatus::InTransit);
});

test('administrator can bulk correct selected shipments back to origin warehouse stage', function () {
    $admin = User::create([
        'name' => 'مدير تصحيح المنشأ',
        'email' => 'bulk-origin-admin@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::Administrator,
        'warehouse_id' => $this->origin->id,
    ]);

    $shipment = journeyUiShipment($this->batch, $this->customer);
    $shipment->packages()->update(['status' => PackageStatus::InTransit]);

    Livewire::actingAs($admin)
        ->test(ListShipments::class)
        ->call('toggleShipmentSelection', $shipment->id)
        ->set('bulkTargetStatus', PackageStatus::ReceivedOrigin->value)
        ->call('bulkAdvanceSelectedShipments')
        ->assertNotified('تم تحديث الشحنات المحددة');

    expect($shipment->packages()->pluck('status')->all())->each->toBe(PackageStatus::ReceivedOrigin);
});

test('administrator can bulk correct newly created packages to a selected journey stage', function () {
    $admin = User::create([
        'name' => 'مدير تصحيح الطرود الجديدة',
        'email' => 'bulk-created-admin@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::Administrator,
        'warehouse_id' => $this->origin->id,
    ]);

    $shipment = journeyUiShipment($this->batch, $this->customer);
    $shipment->packages()->update(['status' => PackageStatus::Created]);

    Livewire::actingAs($admin)
        ->test(ListShipments::class)
        ->call('toggleShipmentSelection', $shipment->id)
        ->set('bulkTargetStatus', PackageStatus::InTransit->value)
        ->call('bulkAdvanceSelectedShipments')
        ->assertNotified('تم تحديث الشحنات المحددة');

    expect($shipment->packages()->pluck('status')->all())->each->toBe(PackageStatus::InTransit);
});
