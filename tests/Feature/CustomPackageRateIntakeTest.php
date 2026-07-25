<?php

use App\Enums\UserRole;
use App\Filament\Pages\ReceiveIntoBatch;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;

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
