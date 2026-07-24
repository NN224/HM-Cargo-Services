<?php

use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Pages\ReceiveIntoBatch;
use App\Models\Batch;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $this->dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->beirut = Warehouse::create(['name' => 'Beirut', 'location' => 'Lebanon']);

    $this->admin = User::create([
        'name' => 'مدير',
        'email' => 'admin_inline@hmcargo.test',
        'password' => 'password',
        'role' => UserRole::Administrator,
        'warehouse_id' => $this->dubai->id,
        'capabilities' => [Capability::PriceShipments->value],
    ]);
});

test('can create a route inline when creating a batch', function () {
    $this->actingAs($this->admin);

    $route = Route::create([
        'name' => 'Dubai → Beirut Inline Test',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->beirut->id,
    ]);

    $batch = Batch::create(['route_id' => $route->id]);

    expect($batch->route_id)->toBe($route->id)
        ->and($route->name)->toBe('Dubai → Beirut Inline Test');
});

test('receive into batch form renders with livewire', function () {
    $this->actingAs($this->admin);

    Livewire::test(ReceiveIntoBatch::class)
        ->assertSuccessful();
});
