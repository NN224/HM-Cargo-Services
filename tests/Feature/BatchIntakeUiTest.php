<?php

use App\Enums\BatchStatus;
use App\Enums\Capability;
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
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->route = Route::create([
        'name' => 'Dubai → Syria',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);

    $this->batch = Batch::create([
        'reference' => 'BCH-2026-0001',
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);

    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->route->id,
        'rate_per_kg_cents' => 300,
    ]);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);

    $this->clerk = User::create([
        'name' => 'موظف', 'email' => 'clerk@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $dubai->id,
    ]);
});

test('an employee without the pricing capability can still receive cargo', function () {
    // D-024: applying a rate agreed before the goods moved is not a pricing
    // decision. Gating it would stop an employee doing most of their job.
    expect($this->clerk->hasCapability(Capability::PriceShipments))->toBeFalse();

    Livewire::actingAs($this->clerk)
        ->test(ReceiveIntoBatch::class)
        ->fillForm([
            'batch_id' => $this->batch->id,
            'customer_id' => $this->customer->id,
            'recipient_is_customer' => true,
            'packages' => [['weight_kg' => 2.0, 'description' => null]],
        ])
        ->call('receive')
        ->assertHasNoFormErrors();

    expect(Shipment::where('batch_id', $this->batch->id)->count())->toBe(1);
});

test('the rate and total are hidden from an employee who may not price', function () {
    Livewire::actingAs($this->clerk)
        ->test(ReceiveIntoBatch::class)
        ->assertFormFieldHidden('rate_per_kg');
});

test('the rate is offered to a user who may price', function () {
    Livewire::actingAs($this->admin)
        ->test(ReceiveIntoBatch::class)
        ->assertFormFieldVisible('rate_per_kg');
});

test('a different recipient is accepted when the box is unticked', function () {
    Livewire::actingAs($this->clerk)
        ->test(ReceiveIntoBatch::class)
        ->fillForm([
            'batch_id' => $this->batch->id,
            'customer_id' => $this->customer->id,
            'recipient_is_customer' => false,
            'recipient_name' => 'سامي',
            'recipient_phone' => '+9613000001',
            'packages' => [['weight_kg' => 2.0, 'description' => null]],
        ])
        ->call('receive')
        ->assertHasNoFormErrors();

    expect(Shipment::first()->recipient_name)->toBe('سامي');
});

test('a customer with no agreed rate is refused and nothing is saved', function () {
    $stranger = Customer::create(['name' => 'غريب', 'phone' => '+971500000099']);

    Livewire::actingAs($this->clerk)
        ->test(ReceiveIntoBatch::class)
        ->fillForm([
            'batch_id' => $this->batch->id,
            'customer_id' => $stranger->id,
            'recipient_is_customer' => true,
            'packages' => [['weight_kg' => 2.0, 'description' => null]],
        ])
        ->call('receive');

    expect(Shipment::count())->toBe(0);
});
