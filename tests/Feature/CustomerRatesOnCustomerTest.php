<?php

use App\Enums\UserRole;
use App\Filament\Resources\CustomerRates\CustomerRateResource;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * A rate belongs to its customer.
 *
 * "العملاء" and "أسعار العملاء" were two navigation entries for one subject,
 * and an operator could not tell why both existed.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);
    $beirut = Warehouse::create(['name' => 'Beirut', 'location' => 'Lebanon']);

    $this->direct = Route::create([
        'name' => 'Dubai → Syria (direct)',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $damascus->id,
    ]);

    $this->viaBeirut = Route::create([
        'name' => 'Dubai → Beirut → Syria',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $damascus->id,
        'transit_warehouse_id' => $beirut->id,
    ]);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);
});

test('a customer page lists their rate on every route they have one for', function () {
    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->direct->id,
        'rate_per_kg_cents' => 300,
    ]);

    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->viaBeirut->id,
        'rate_per_kg_cents' => 275,
    ]);

    Livewire::actingAs($this->admin)
        ->test(EditCustomer::class, ['record' => $this->customer->getRouteKey()])
        ->assertSee('Dubai → Syria (direct)')
        ->assertSee('Dubai → Beirut → Syria')
        // Dollars on screen; cents in storage.
        ->assertSee('3.00')
        ->assertSee('2.75');
});

test('a customer with no agreed rates says so rather than showing an empty box', function () {
    Livewire::actingAs($this->admin)
        ->test(EditCustomer::class, ['record' => $this->customer->getRouteKey()])
        ->assertSee('لا توجد أسعار متفق عليها');
});

test('customer rates no longer take a navigation entry of their own', function () {
    expect(CustomerRateResource::shouldRegisterNavigation())->toBeFalse();
});

test('the rates pages stay reachable, because the edit link depends on them', function () {
    // Hiding a resource from navigation must not unregister its routes: the
    // rate is still changed there, behind the confirmation that guards it.
    $rate = CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->direct->id,
        'rate_per_kg_cents' => 300,
    ]);

    expect(CustomerRateResource::getUrl('edit', ['record' => $rate]))
        ->toBeString()
        ->toContain('customer-rates');
});
