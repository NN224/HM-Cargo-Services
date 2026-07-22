<?php

use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Filament\Widgets\OperationalStatsWidget;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * The dashboard's operational counts.
 *
 * These are the two numbers that tell an operator to act — cargo to price
 * onto a batch, and cargo to release — and they show for everyone, money-
 * holder or not.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->clerk = User::create([
        'name' => 'موظف', 'email' => 'clerk@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $dubai->id,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);
});

function shipmentWith(array $attributes, bool $withPackage = true): Shipment
{
    $shipment = Shipment::create(array_merge([
        'customer_id' => test()->customer->id,
        'recipient_name' => 'سامي',
        'recipient_phone' => '+9613000001',
        'destination_warehouse_id' => test()->damascus->id,
    ], $attributes));

    if ($withPackage) {
        $shipment->packages()->create(['weight_kg' => 1.0]);
    }

    return $shipment->fresh();
}

test('the operational counts are visible to a capability-less employee', function () {
    // canView() checks the authenticated user, so one must be logged in for
    // this assertion to exercise anything — the panel itself never calls it
    // anonymously (it sits behind auth middleware).
    $this->actingAs($this->clerk);

    expect(OperationalStatsWidget::canView())->toBeTrue();
});

test('awaiting-a-batch counts shipments with packages and no batch', function () {
    // Counted: has packages, no batch.
    shipmentWith(['batch_id' => null]);
    shipmentWith(['batch_id' => null]);
    // Not counted: no packages yet (an empty draft).
    shipmentWith(['batch_id' => null], withPackage: false);

    Livewire::actingAs($this->clerk)
        ->test(OperationalStatsWidget::class)
        ->assertSee('بانتظار رحلة')
        ->assertSee('2');
});

test('ready-for-collection counts shipments in that status', function () {
    $ready = shipmentWith(['batch_id' => null]);
    $ready->forceFill(['status' => ShipmentStatus::ReadyForCollection])->save();

    // Not ready.
    shipmentWith(['batch_id' => null]);

    Livewire::actingAs($this->clerk)
        ->test(OperationalStatsWidget::class)
        ->assertSee('جاهزة للتسليم')
        ->assertSee('1');
});
