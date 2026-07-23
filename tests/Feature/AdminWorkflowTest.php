<?php

use App\Enums\UserRole;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $warehouse = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => $warehouse->id,
    ]);

    $this->employee = User::create([
        'name' => 'موظف', 'email' => 'clerk@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $warehouse->id,
    ]);
});

test('creating a customer returns to the list, not another form', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateCustomer::class)
        ->fillForm(['name' => 'نبيل', 'phone' => '+971500001111'])
        ->call('create')
        ->assertRedirect('/admin/customers');
});

test('saving an edited customer returns to the list', function () {
    $this->actingAs($this->admin);
    $customer = Customer::create(['name' => 'قديم', 'phone' => '+971500002222']);

    Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->fillForm(['name' => 'جديد', 'phone' => '+971500002222'])
        ->call('save')
        ->assertRedirect('/admin/customers');

    expect($customer->fresh()->name)->toBe('جديد');
});

test('an administrator can deactivate a customer from the list', function () {
    $this->actingAs($this->admin);
    $customer = Customer::create(['name' => 'نشط', 'phone' => '+971500003333']);

    expect($customer->is_active)->toBeTrue();

    Livewire::test(ListCustomers::class)
        ->callTableAction('toggleActive', $customer);

    expect($customer->fresh()->is_active)->toBeFalse();
});

test('deactivation is reversible', function () {
    $this->actingAs($this->admin);
    $customer = Customer::create([
        'name' => 'معطل', 'phone' => '+971500004444', 'is_active' => false,
    ]);

    Livewire::test(ListCustomers::class)
        ->callTableAction('toggleActive', $customer);

    expect($customer->fresh()->is_active)->toBeTrue();
});

test('deactivating never destroys the record', function () {
    $this->actingAs($this->admin);
    $customer = Customer::create(['name' => 'باقٍ', 'phone' => '+971500005555']);

    Livewire::test(ListCustomers::class)
        ->callTableAction('toggleActive', $customer);

    // The row must still exist: history depends on it (AGENTS.md).
    expect(Customer::find($customer->id))->not->toBeNull()
        ->and(Customer::count())->toBe(1);
});

test('an employee is not offered the deactivate action', function () {
    $this->actingAs($this->employee);
    $customer = Customer::create(['name' => 'محمي', 'phone' => '+971500006666']);

    Livewire::test(ListCustomers::class)
        ->assertTableActionHidden('toggleActive', $customer);
});
