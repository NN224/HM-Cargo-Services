<?php

use App\Enums\UserRole;
use App\Filament\Widgets\RecentShipmentsWidget;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * Recent shipments, shown to everyone and carrying no money — an at-a-glance
 * of today's activity without opening the full list.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->clerk = User::create([
        'name' => 'موظف', 'email' => 'clerk@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $dubai->id,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);

    $this->shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'سامي',
        'recipient_phone' => '+9613000001',
        'destination_warehouse_id' => $damascus->id,
    ]);
    // Give it a charge, so a money column would have something to leak.
    $this->shipment->forceFill(['final_charge_cents' => 9999])->save();
});

test('recent shipments are visible to a capability-less employee', function () {
    // canView() checks the authenticated user, so one must be logged in for
    // this assertion to exercise anything — the panel itself never calls it
    // anonymously (it sits behind auth middleware).
    $this->actingAs($this->clerk);

    expect(RecentShipmentsWidget::canView())->toBeTrue();
});

test('the table shows the shipment and its customer, but no money', function () {
    Livewire::actingAs($this->clerk)
        ->test(RecentShipmentsWidget::class)
        ->assertCanSeeTableRecords([$this->shipment])
        ->assertSee($this->shipment->reference)
        ->assertSee('أحمد')
        // The $99.99 charge must not appear — no money column.
        ->assertDontSee('99.99');
});
