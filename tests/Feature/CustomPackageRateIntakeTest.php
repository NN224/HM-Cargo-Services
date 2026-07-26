<?php

use App\Enums\UserRole;
use App\Filament\Pages\ReceiveIntoBatch;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => UserRole::Administrator,
    ]);

    $this->dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->syria = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->route = Route::create([
        'name' => 'Dubai → Syria',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->syria->id,
    ]);

    $this->customer = Customer::create([
        'name' => 'Sami',
        'phone' => '+971500000001',
    ]);

    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->route->id,
        'rate_per_kg_cents' => 925, // $9.25 / kg
    ]);

    $this->batch = Batch::create([
        'route_id' => $this->route->id,
    ]);
});

test('intake ui calculates final charge with custom per package rates', function () {
    Livewire::actingAs($this->admin)
        ->test(ReceiveIntoBatch::class)
        ->fillForm([
            'batch_id' => $this->batch->id,
            'customer_id' => $this->customer->id,
            'recipient_is_customer' => true,
            'packages' => [
                [
                    'weight_kg' => '2.0000',
                    'description' => 'Shein Clothes',
                    'pricing_method' => 'per_kg',
                    'custom_rate_per_kg' => '10.00',
                ],
                [
                    'weight_kg' => '1.0000',
                    'description' => 'Cosmetics',
                    'pricing_method' => 'per_kg',
                    'custom_rate_per_kg' => '18.00', // Custom: $18.00 * 1 = $18.00 (1800 cents)
                ],
            ],
        ])
        ->assertFormSet(['final_charge_usd' => '38.00']);
});

test('existing package rate fills every unpriced package in the same shipment', function () {
    CustomerRate::query()->delete();

    $shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => $this->customer->name,
        'recipient_phone' => $this->customer->phone,
        'destination_warehouse_id' => $this->syria->id,
    ]);
    $shipment->forceFill(['batch_id' => $this->batch->id])->save();

    $shipment->packages()->create([
        'weight_kg' => '24.0000',
        'description' => 'Shein',
        'custom_rate_per_kg_cents' => 5600,
    ]);
    $shipment->packages()->create([
        'weight_kg' => '5.0000',
        'description' => 'Second package',
        'custom_rate_per_kg_cents' => null,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ReceiveIntoBatch::class)
        ->fillForm(['batch_id' => $this->batch->id])
        ->fillForm(['customer_id' => $this->customer->id])
        ->assertFormSet(function (array $state): array {
            $packages = array_values($state['packages']);

            expect($packages[0]['custom_rate_per_kg'])->toBe('56.00')
                ->and($packages[1]['custom_rate_per_kg'])->toBe('56.00');

            return ['final_charge_usd' => '1624.00'];
        });
});
