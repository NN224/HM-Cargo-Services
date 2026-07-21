<?php

use App\Enums\BatchStatus;
use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Resources\Batches\Pages\ListBatches;
use App\Filament\Resources\Shipments\Pages\ListShipments;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * Delete buttons for shipments and batches.
 *
 * The safe-deletion logic and its rules were already built and tested; only
 * these two tables were missing the button, so an operator could delete a
 * customer but not a shipment they had just mistyped.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->route = Route::create([
        'name' => 'Dubai → Syria',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);

    $this->clerk = User::create([
        'name' => 'موظف', 'email' => 'clerk@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $dubai->id,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);
});

function loneShipment(): Shipment
{
    $shipment = Shipment::create([
        'customer_id' => test()->customer->id,
        'recipient_name' => 'سامي',
        'recipient_phone' => '+9613000001',
        'destination_warehouse_id' => test()->damascus->id,
    ]);
    $shipment->packages()->create(['weight_kg' => 3.0]);

    return $shipment->fresh();
}

test('an unassigned shipment can be deleted from the table', function () {
    $shipment = loneShipment();

    Livewire::actingAs($this->admin)
        ->test(ListShipments::class)
        ->callTableAction('delete', $shipment->id);

    expect(Shipment::find($shipment->id))->toBeNull();
});

test('a shipment attached to a batch is refused, and the batch is named', function () {
    $shipment = loneShipment();

    $batch = Batch::create([
        'reference' => 'BCH-2026-0001',
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
    ]);
    $shipment->forceFill(['batch_id' => $batch->id])->save();

    Livewire::actingAs($this->admin)
        ->test(ListShipments::class)
        ->callTableAction('delete', $shipment->id);

    // Still there. Deleting it would remove weight and revenue from a batch
    // that has already counted them.
    expect(Shipment::find($shipment->id))->not->toBeNull();
});

test('a batch still holding shipments cannot be deleted', function () {
    $shipment = loneShipment();

    $batch = Batch::create([
        'reference' => 'BCH-2026-0002',
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
    ]);
    $shipment->forceFill(['batch_id' => $batch->id])->save();

    Livewire::actingAs($this->admin)
        ->test(ListBatches::class)
        ->callTableAction('delete', $batch->id);

    expect(Batch::find($batch->id))->not->toBeNull();
});

test('an empty batch can be deleted', function () {
    $batch = Batch::create([
        'reference' => 'BCH-2026-0003',
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ListBatches::class)
        ->callTableAction('delete', $batch->id);

    expect(Batch::find($batch->id))->toBeNull();
});

test('an employee without the delete capability is not offered the action', function () {
    $shipment = loneShipment();

    Livewire::actingAs($this->clerk)
        ->test(ListShipments::class)
        ->assertTableActionHidden('delete', $shipment->id);
});

test('granting the capability reveals the action', function () {
    $shipment = loneShipment();

    $this->clerk->grantCapability(Capability::DeleteRecords);

    Livewire::actingAs($this->clerk->fresh())
        ->test(ListShipments::class)
        ->assertTableActionVisible('delete', $shipment->id);
});
