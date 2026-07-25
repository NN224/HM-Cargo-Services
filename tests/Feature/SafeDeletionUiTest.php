<?php

use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Routes\Pages\ListRoutes;
use App\Filament\Resources\Warehouses\Pages\ListWarehouses;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $this->dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);
    $this->beirut = Warehouse::create(['name' => 'Beirut', 'location' => 'Lebanon']);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test', 'password' => 'x',
        'role' => UserRole::Administrator,
    ]);

    $this->clerk = User::create([
        'name' => 'موظف', 'email' => 'clerk@hmcargo.test', 'password' => 'x',
        'role' => UserRole::WarehouseEmployee, 'warehouse_id' => $this->dubai->id,
    ]);

    $this->deleter = User::create([
        'name' => 'محذف', 'email' => 'del@hmcargo.test', 'password' => 'x',
        'role' => UserRole::WarehouseEmployee, 'warehouse_id' => $this->dubai->id,
        'capabilities' => [Capability::DeleteRecords->value],
    ]);
});

// ------------------------------------------------------------------ customers

test('admin can delete an orphaned customer via the table', function () {
    $customer = Customer::create(['name' => 'للحذف', 'phone' => '+9715000111']);

    Livewire::actingAs($this->admin)
        ->test(ListCustomers::class)
        ->assertSee('للحذف')
        ->callTableAction('delete', $customer->id);

    expect(Customer::find($customer->id))->toBeNull();
});

test('deleting a customer with shipments shows an arabic error and does not delete', function () {
    $customer = Customer::create(['name' => 'محمي', 'phone' => '+9715000222']);
    Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'سامي', 'recipient_phone' => '+9613000001',
    ]);

    Livewire::actingAs($this->admin)
        ->test(ListCustomers::class)
        ->callTableAction('delete', $customer->id)
        ->assertNotified('لا يمكن الحذف');

    expect(Customer::find($customer->id))->not->toBeNull();
});

test('user without delete capability cannot call the delete action', function () {
    $customer = Customer::create(['name' => 'غير محذوف', 'phone' => '+9715000333']);

    Livewire::actingAs($this->clerk)
        ->test(ListCustomers::class)
        ->assertTableActionHidden('delete', $customer->id);
});

test('employee with delete capability can delete an orphaned customer', function () {
    $customer = Customer::create(['name' => 'للحذف', 'phone' => '+9715000444']);

    Livewire::actingAs($this->deleter)
        ->test(ListCustomers::class)
        ->assertTableActionVisible('delete', $customer->id)
        ->callTableAction('delete', $customer->id);

    expect(Customer::find($customer->id))->toBeNull();
});

// -------------------------------------------------------------------- routes

test('admin can delete an orphaned route via the table', function () {
    $route = Route::create([
        'name' => 'مسار غير مستخدم',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ListRoutes::class)
        ->assertSee('مسار غير مستخدم')
        ->callTableAction('delete', $route->id);

    expect(Route::find($route->id))->toBeNull();
});

test('deleting a route with a batch shows an arabic error and does not delete', function () {
    $route = Route::create([
        'name' => 'مسار مستخدم',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);
    Batch::create(['route_id' => $route->id]);

    Livewire::actingAs($this->admin)
        ->test(ListRoutes::class)
        ->callTableAction('delete', $route->id)
        ->assertNotified('لا يمكن الحذف');

    expect(Route::find($route->id))->not->toBeNull();
});

test('employee with delete capability can delete an orphaned route', function () {
    $route = Route::create([
        'name' => 'مسار للحذف',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);

    Livewire::actingAs($this->deleter)
        ->test(ListRoutes::class)
        ->callTableAction('delete', $route->id);

    expect(Route::find($route->id))->toBeNull();
});

// ---------------------------------------------------------------- warehouses

test('admin can delete an orphaned warehouse via the table', function () {
    $orphan = Warehouse::create(['name' => 'مستودع منعزل', 'location' => 'Test']);

    Livewire::actingAs($this->admin)
        ->test(ListWarehouses::class)
        ->assertSee('مستودع منعزل')
        ->callTableAction('delete', $orphan->id);

    expect(Warehouse::find($orphan->id))->toBeNull();
});

test('deleting a warehouse with routes shows an arabic error and does not delete', function () {
    $warehouse = Warehouse::create(['name' => 'مستودع مرتبط', 'location' => 'Test']);
    Route::create([
        'name' => 'مسار',
        'origin_warehouse_id' => $warehouse->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ListWarehouses::class)
        ->callTableAction('delete', $warehouse->id)
        ->assertNotified('لا يمكن الحذف');

    expect(Warehouse::find($warehouse->id))->not->toBeNull();
});

test('employee with delete capability sees the delete action on their warehouse', function () {
    Livewire::actingAs($this->deleter)
        ->test(ListWarehouses::class)
        ->assertTableActionVisible('delete', $this->dubai->id);
});
