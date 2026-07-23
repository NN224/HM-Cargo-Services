<?php

use App\Enums\UserRole;
use App\Filament\Pages\ReceiveIntoBatch;
use App\Filament\Resources\Shipments\Pages\CreateShipment;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * Two doors that both create a shipment.
 *
 * Neither is redundant: cargo arrives sometimes with a batch already open and
 * sometimes before one exists. They were only silent about which is which,
 * and an operator opening the menu could not tell them apart.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => $dubai->id,
    ]);
});

test('the shipment form says the shipment is not yet on a batch', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateShipment::class)
        ->assertSee('غير مرتبطة برحلة');
});

test('the intake screen says it prices on the spot', function () {
    Livewire::actingAs($this->admin)
        ->test(ReceiveIntoBatch::class)
        ->assertSee('رحلة مفتوحة');
});
