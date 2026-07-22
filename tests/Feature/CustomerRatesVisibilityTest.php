<?php

use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * A customer's rate per kilogram is money (D-024). It renders on the customer
 * page, which an employee needs for receiving cargo — so the rate section is
 * hidden for anyone without the customers capability, while the rest of the
 * page stays usable.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $route = Route::create([
        'name' => 'Dubai → Syria',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $damascus->id,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);

    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $route->id,
        'rate_per_kg_cents' => 300,
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
});

test('the rate figure is not shown to an employee without the customers capability', function () {
    expect($this->clerk->hasCapability(Capability::ManageCustomers))->toBeFalse();

    Livewire::actingAs($this->clerk)
        ->test(EditCustomer::class, ['record' => $this->customer->getRouteKey()])
        // The rates section and its heading are absent...
        ->assertDontSee('الأسعار المتفق عليها')
        ->assertDontSee('3.00')
        // ...but the customer page still renders.
        ->assertSee('أحمد');
});

test('an administrator sees the rates', function () {
    Livewire::actingAs($this->admin)
        ->test(EditCustomer::class, ['record' => $this->customer->getRouteKey()])
        ->assertSee('الأسعار المتفق عليها')
        ->assertSee('3.00');
});

test('an employee who holds the customers capability sees the rates', function () {
    $this->clerk->grantCapability(Capability::ManageCustomers);

    Livewire::actingAs($this->clerk->fresh())
        ->test(EditCustomer::class, ['record' => $this->customer->getRouteKey()])
        ->assertSee('الأسعار المتفق عليها')
        ->assertSee('3.00');
});
