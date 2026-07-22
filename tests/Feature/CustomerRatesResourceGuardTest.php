<?php

use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Resources\CustomerRates\CustomerRateResource;
use App\Filament\Resources\CustomerRates\Pages\ListCustomerRates;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * The standalone customer-rates screen is gated by the customers capability.
 *
 * It is hidden from the navigation (its rates now read on the customer page),
 * but its routes stayed registered so the customer page's edit link resolves.
 * With no capability guard, an employee reaching /admin/customer-rates by URL
 * saw every customer's rate per kilogram — the real leak, on a different
 * screen than the one first suspected.
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

test('an employee without the customers capability cannot reach the rates screen', function () {
    $this->actingAs($this->clerk);

    // The leak: reachable by URL despite being hidden from the navigation.
    expect(CustomerRateResource::canViewAny())->toBeFalse();
});

test('a direct visit to the rates list is forbidden for such an employee', function () {
    $this->actingAs($this->clerk)
        ->get(CustomerRateResource::getUrl('index'))
        ->assertForbidden();
});

test('an administrator reaches the rates screen', function () {
    $this->actingAs($this->admin);

    expect(CustomerRateResource::canViewAny())->toBeTrue();

    Livewire::actingAs($this->admin)
        ->test(ListCustomerRates::class)
        ->assertSuccessful();
});

test('an employee who holds the customers capability reaches it, so the edit link still works', function () {
    $this->clerk->grantCapability(Capability::ManageCustomers);
    $this->actingAs($this->clerk->fresh());

    expect(CustomerRateResource::canViewAny())->toBeTrue();
});
