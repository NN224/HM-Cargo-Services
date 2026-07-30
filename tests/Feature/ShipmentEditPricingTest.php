<?php

use App\Enums\UserRole;
use App\Filament\Resources\Shipments\Pages\EditShipment;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => UserRole::Administrator,
    ]);

    $this->destination = Warehouse::create([
        'name' => 'Beirut',
        'location' => 'Lebanon',
    ]);

    $this->customer = Customer::create([
        'name' => 'Ahmad',
        'phone' => '+96170000000',
    ]);

    $this->shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'Ahmad',
        'recipient_phone' => '+96170000000',
        'destination_warehouse_id' => $this->destination->id,
    ]);
});

test('shipment edit loads every package pricing field and the shipment total', function () {
    $this->shipment->packages()->create([
        'weight_kg' => '12.0000',
        'custom_rate_per_kg_cents' => 5600,
    ]);

    $fixedPackage = $this->shipment->packages()->create([
        'weight_kg' => '30.0000',
    ]);
    $fixedPackage->forceFill(['fixed_charge_cents' => 2500])->save();

    $this->shipment->forceFill(['final_charge_cents' => 69700])->save();

    Livewire::actingAs($this->admin)
        ->test(EditShipment::class, ['record' => $this->shipment->getRouteKey()])
        ->assertFormSet(function (array $state): array {
            $packages = array_values($state['packages']);

            expect($packages[0]['pricing_method'])->toBe('per_kg')
                ->and($packages[0]['custom_rate_per_kg'])->toBe(56.0)
                ->and($packages[1]['pricing_method'])->toBe('fixed')
                ->and($packages[1]['fixed_charge_usd'])->toBe(25.0)
                ->and($state['final_charge_usd'])->toBe(697.0);

            return [];
        });
});

test('shipment edit saves package pricing and the shipment total to the original records', function () {
    $package = $this->shipment->packages()->create([
        'weight_kg' => '12.0000',
        'description' => 'Original package',
        'custom_rate_per_kg_cents' => 5600,
    ]);
    $this->shipment->forceFill(['final_charge_cents' => 67200])->save();

    Livewire::actingAs($this->admin)
        ->test(EditShipment::class, ['record' => $this->shipment->getRouteKey()])
        ->fillForm([
            'packages' => [
                "record-{$package->id}" => [
                    'weight_kg' => '12.0000',
                    'description' => 'Updated package',
                    'source_barcode' => null,
                    'pricing_method' => 'fixed',
                    'custom_rate_per_kg' => null,
                    'fixed_charge_usd' => '120.50',
                ],
            ],
            'final_charge_usd' => '120.50',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($package->fresh()->description)->toBe('Updated package')
        ->and($package->fresh()->custom_rate_per_kg_cents)->toBeNull()
        ->and($package->fresh()->fixed_charge_cents)->toBe(12050)
        ->and($this->shipment->fresh()->final_charge_cents)->toBe(12050);
});

test('shipment edit recalculates the displayed total when a package price changes', function () {
    $package = $this->shipment->packages()->create([
        'weight_kg' => '12.0000',
        'custom_rate_per_kg_cents' => 5600,
    ]);
    $this->shipment->forceFill(['final_charge_cents' => 67200])->save();

    Livewire::actingAs($this->admin)
        ->test(EditShipment::class, ['record' => $this->shipment->getRouteKey()])
        ->set("data.packages.record-{$package->id}.custom_rate_per_kg", '60.00')
        ->assertFormSet(['final_charge_usd' => '720.00']);
});
