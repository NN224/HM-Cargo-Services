<?php

use App\Enums\UserRole;
use App\Filament\Resources\Shipments\Pages\CreateShipment;
use App\Filament\Resources\Shipments\Pages\ListShipments;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $warehouse = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);

    // Where the cargo is going, as opposed to where it is accepted. A shipment
    // declares this itself so that assigning it to a batch bound elsewhere can
    // be refused (data-model.md §7).
    $this->destination = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => $warehouse->id,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);

    $this->actingAs($this->admin);
});

test('a shipment with several packages can be created from the panel', function () {
    Livewire::test(CreateShipment::class)
        ->fillForm([
            'customer_id' => $this->customer->id,
            'recipient_name' => 'سامي',
            'recipient_phone' => '+9613000001',
            'destination_warehouse_id' => $this->destination->id,
            'packages' => [
                ['weight_kg' => 2.5, 'description' => 'ملابس'],
                ['weight_kg' => 1.25, 'description' => 'أدوات'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect('/shipments');

    $shipment = Shipment::first();

    expect($shipment->packages)->toHaveCount(2)
        ->and((string) $shipment->total_weight_kg)->toBe('3.7500');
});

test('the total weight follows the packages without anyone recalculating by hand', function () {
    $shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'سامي', 'recipient_phone' => '+9613000001',
    ]);

    $package = $shipment->packages()->create(['weight_kg' => 4]);
    expect((string) $shipment->fresh()->total_weight_kg)->toBe('4.0000');

    $package->update(['weight_kg' => 6.5]);
    expect((string) $shipment->fresh()->total_weight_kg)->toBe('6.5000');

    $package->delete();
    expect((string) $shipment->fresh()->total_weight_kg)->toBe('0.0000');
});

test('a shipment cannot be saved without at least one package', function () {
    Livewire::test(CreateShipment::class)
        ->fillForm([
            'customer_id' => $this->customer->id,
            'recipient_name' => 'سامي',
            'recipient_phone' => '+9613000001',
            'destination_warehouse_id' => $this->destination->id,
            'packages' => [],
        ])
        ->call('create')
        ->assertHasFormErrors(['packages']);

    expect(Shipment::count())->toBe(0);
});

test('the shipments list is labelled in Arabic and shows the reference', function () {
    $shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'سامي', 'recipient_phone' => '+9613000001',
    ]);
    $shipment->packages()->create(['weight_kg' => 1]);

    Livewire::test(ListShipments::class)
        ->assertSee('رقم الشحنة', escape: false)
        ->assertSee('المستلم', escape: false)
        ->assertSee('الوزن', escape: false)
        ->assertSee($shipment->reference)
        // The random token must never be printed in the staff table.
        ->assertDontSee($shipment->public_token);
});

test('the form offers no recipient address field', function () {
    // D-019 — asserted at the UI boundary too, not just the schema.
    Livewire::test(CreateShipment::class)
        ->assertDontSee('عنوان المستلم', escape: false)
        ->assertDontSee('recipient_address');
});
