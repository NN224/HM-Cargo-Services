<?php

use App\Enums\PackageStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Shipments\Pages\ViewShipment;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * Opening a shipment.
 *
 * Packages existed in the product only as a count on the list, so no screen
 * would tell you a barcode, a single package's weight, or which box had not
 * arrived. An operator asked "which of these three hasn't turned up?" had
 * nowhere to look.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);

    $this->shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'سامي',
        'recipient_phone' => '+9613000001',
        'destination_warehouse_id' => $this->damascus->id,
    ]);
});

test('every package appears with its barcode and weight', function () {
    $this->shipment->packages()->create(['weight_kg' => 2.5, 'description' => 'ملابس']);
    $this->shipment->packages()->create(['weight_kg' => 1.25, 'description' => 'أدوات']);

    $barcodes = $this->shipment->packages()->pluck('barcode');

    Livewire::actingAs($this->admin)
        ->test(ViewShipment::class, ['record' => $this->shipment->getRouteKey()])
        ->assertSee($barcodes[0])
        ->assertSee($barcodes[1])
        ->assertSee('2.5')
        ->assertSee('1.25');
});

test('each package shows its own status, not the shipment stage', function () {
    // The whole reason to open a shipment sitting at partial_at_destination is
    // to learn which box is missing. A page that renders one status for every
    // row cannot answer that, so the statuses here are deliberately different.
    $arrived = $this->shipment->packages()->create(['weight_kg' => 1.0]);
    $missing = $this->shipment->packages()->create(['weight_kg' => 1.0]);

    $arrived->forceFill(['status' => PackageStatus::ArrivedDestination])->save();
    $missing->forceFill(['status' => PackageStatus::Missing])->save();

    Livewire::actingAs($this->admin)
        ->test(ViewShipment::class, ['record' => $this->shipment->getRouteKey()])
        ->assertSee(PackageStatus::ArrivedDestination->label())
        ->assertSee(PackageStatus::Missing->label());
});

test('a cancelled package is still listed', function () {
    // The page records what was received, not only what is billable. A box
    // that was cancelled still physically passed through the counter (D-023).
    $cancelled = $this->shipment->packages()->create(['weight_kg' => 3.0]);
    $cancelled->forceFill(['status' => PackageStatus::Cancelled])->save();

    Livewire::actingAs($this->admin)
        ->test(ViewShipment::class, ['record' => $this->shipment->getRouteKey()])
        ->assertSee($cancelled->fresh()->barcode)
        ->assertSee(PackageStatus::Cancelled->label());
});

test('the supplier barcode is shown when one was recorded', function () {
    $this->shipment->packages()->create([
        'weight_kg' => 1.0,
        'source_barcode' => 'SUP-99887',
    ]);

    Livewire::actingAs($this->admin)
        ->test(ViewShipment::class, ['record' => $this->shipment->getRouteKey()])
        ->assertSee('SUP-99887');
});
