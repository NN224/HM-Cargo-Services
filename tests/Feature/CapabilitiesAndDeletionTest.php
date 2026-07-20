<?php

use App\Enums\Capability;
use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;

beforeEach(function () {
    $this->dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test', 'password' => 'x',
        'role' => UserRole::Administrator,
    ]);

    $this->clerk = User::create([
        'name' => 'موظف', 'email' => 'clerk@hmcargo.test', 'password' => 'x',
        'role' => UserRole::WarehouseEmployee, 'warehouse_id' => $this->dubai->id,
    ]);
});

// ---------------------------------------------------------------- capabilities

test('an administrator holds every capability without being granted any', function () {
    foreach (Capability::cases() as $capability) {
        expect($this->admin->hasCapability($capability))->toBeTrue();
    }
});

test('a plain employee holds no capabilities by default', function () {
    foreach (Capability::cases() as $capability) {
        expect($this->clerk->hasCapability($capability))->toBeFalse();
    }
});

test('an administrator can grant a single capability to an employee', function () {
    $this->clerk->grantCapability(Capability::RecordPayments);

    expect($this->clerk->fresh()->hasCapability(Capability::RecordPayments))->toBeTrue()
        // Granting one must not grant the rest.
        ->and($this->clerk->fresh()->hasCapability(Capability::PriceShipments))->toBeFalse();
});

test('a capability can be revoked again', function () {
    $this->clerk->grantCapability(Capability::PriceShipments);
    expect($this->clerk->fresh()->hasCapability(Capability::PriceShipments))->toBeTrue();

    $this->clerk->revokeCapability(Capability::PriceShipments);
    expect($this->clerk->fresh()->hasCapability(Capability::PriceShipments))->toBeFalse();
});

test('capabilities never widen a warehouse employee beyond their warehouse', function () {
    // A capability grants an action, never another warehouse's data (D-003).
    $this->clerk->grantCapability(Capability::ManageCustomers);

    expect($this->clerk->fresh()->canAccessWarehouse($this->damascus->id))->toBeFalse()
        ->and($this->clerk->fresh()->canAccessWarehouse($this->dubai->id))->toBeTrue();
});

test('unknown values stored on the record are ignored', function () {
    // Guards against a hand-edited row granting something that does not exist.
    $this->clerk->forceFill(['capabilities' => ['not_a_real_capability']])->save();

    expect($this->clerk->fresh()->capabilityList())->toBe([]);
});

// ------------------------------------------------------------------- deletion

test('a customer with nothing attached can be deleted', function () {
    $customer = Customer::create(['name' => 'خطأ', 'phone' => '+9715000111']);

    expect($customer->canBeDeleted())->toBeTrue()
        ->and($customer->deletionBlockers())->toBe([]);
});

test('a customer with shipments cannot be deleted, and the reason is named', function () {
    $customer = Customer::create(['name' => 'فعّال', 'phone' => '+9715000222']);
    Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'س', 'recipient_phone' => '+9613000001',
    ]);

    expect($customer->canBeDeleted())->toBeFalse()
        ->and($customer->deletionBlockers())->toContain('شحنات');
});

test('deleting a depended-upon record throws rather than cascading', function () {
    $customer = Customer::create(['name' => 'محمي', 'phone' => '+9715000333']);
    Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'س', 'recipient_phone' => '+9613000001',
    ]);

    $customer->deleteSafely();
})->throws(DomainException::class);

test('a shipment that was never priced can be deleted', function () {
    $customer = Customer::create(['name' => 'ع', 'phone' => '+9715000444']);
    $shipment = Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'س', 'recipient_phone' => '+9613000001',
    ]);
    $shipment->packages()->create(['weight_kg' => 2]);

    // Its own packages are part of it, not dependants: deleting the shipment
    // takes them with it.
    expect($shipment->canBeDeleted())->toBeTrue();

    $shipment->deleteSafely();

    expect(Shipment::count())->toBe(0)
        ->and(App\Models\Package::count())->toBe(0);
});

test('a priced shipment inside a batch cannot be deleted', function () {
    $customer = Customer::create(['name' => 'م', 'phone' => '+9715000555']);
    $route = Route::create([
        'name' => 'Dubai → Damascus',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);
    CustomerRate::create([
        'customer_id' => $customer->id, 'route_id' => $route->id,
        'rate_per_kg_cents' => 450,
    ]);

    $shipment = Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'س', 'recipient_phone' => '+9613000001',
    ]);
    $shipment->packages()->create(['weight_kg' => 5]);

    app(App\Services\BatchAssignmentService::class)
        ->assign($shipment->fresh(), Batch::create(['route_id' => $route->id]));

    expect($shipment->fresh()->canBeDeleted())->toBeFalse();
});

test('a route no batch has used can be deleted', function () {
    $route = Route::create([
        'name' => 'مسار غير مستخدم',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);

    expect($route->canBeDeleted())->toBeTrue();
});

test('a warehouse a route depends on cannot be deleted', function () {
    Route::create([
        'name' => 'مسار',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);

    expect($this->dubai->canBeDeleted())->toBeFalse()
        ->and($this->dubai->deletionBlockers())->toContain('مسارات');
});

test('only a user holding the delete capability may delete', function () {
    $customer = Customer::create(['name' => 'ت', 'phone' => '+9715000666']);

    expect($this->clerk->can('delete', $customer))->toBeFalse();

    $this->clerk->grantCapability(Capability::DeleteRecords);

    expect($this->clerk->fresh()->can('delete', $customer))->toBeTrue()
        ->and($this->admin->can('delete', $customer))->toBeTrue();
});

test('the delete capability does not override a dependency', function () {
    $customer = Customer::create(['name' => 'ث', 'phone' => '+9715000777']);
    Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'س', 'recipient_phone' => '+9613000001',
    ]);

    $this->clerk->grantCapability(Capability::DeleteRecords);

    // Permission answers "may you", dependencies answer "is it safe". Both
    // must agree before anything is removed.
    expect($this->clerk->fresh()->can('delete', $customer))->toBeTrue()
        ->and($customer->canBeDeleted())->toBeFalse();
});
