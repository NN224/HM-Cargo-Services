<?php

use App\Enums\BatchStatus;
use App\Enums\UserRole;
use App\Filament\Pages\ReceiveIntoBatch;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\BatchIntakeService;
use Livewire\Livewire;

/**
 * AGENTS.md: a warehouse employee sees and operates only their assigned
 * warehouse. A batch's route is relevant at its origin, transit and
 * destination warehouse (the same rule BatchResource::scopeRouteQuery()
 * already applies to the batches list) — an employee outside all three
 * must not be offered, or able to force, intake onto that batch.
 */
beforeEach(function () {
    $this->dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->beirut = Warehouse::create(['name' => 'Beirut', 'location' => 'Lebanon']);
    $this->damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    // A route the Damascus warehouse has no part in at all.
    $this->route = Route::create([
        'name' => 'Dubai → Beirut',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->beirut->id,
    ]);

    $this->batch = Batch::create([
        'reference' => 'BCH-2026-0002',
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);

    // An existing rate so the scenario only exercises warehouse scoping,
    // never the separate ManageCustomers gate covered by BatchIntakeRateTest.
    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->route->id,
        'rate_per_kg_cents' => 300,
    ]);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'scope-admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);

    // Assigned to a warehouse this batch's route never touches.
    $this->damascusEmployee = User::create([
        'name' => 'موظف دمشق', 'email' => 'damascus@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $this->damascus->id,
    ]);

    // Assigned to the batch's own origin warehouse — the control case that
    // must keep working.
    $this->dubaiEmployee = User::create([
        'name' => 'موظف دبي', 'email' => 'dubai@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $this->dubai->id,
    ]);
});

test('an employee outside the route is not offered the batch', function () {
    Livewire::actingAs($this->damascusEmployee)
        ->test(ReceiveIntoBatch::class)
        ->assertFormFieldExists('batch_id', checkFieldUsing: fn ($field): bool => ! array_key_exists($this->batch->id, $field->getOptions()));
});

test('an employee whose warehouse the route touches is offered the batch', function () {
    Livewire::actingAs($this->dubaiEmployee)
        ->test(ReceiveIntoBatch::class)
        ->assertFormFieldExists('batch_id', checkFieldUsing: fn ($field): bool => array_key_exists($this->batch->id, $field->getOptions()));
});

test('an employee outside the route cannot receive into it even when the id is supplied directly', function () {
    // Filament's own Select validation already rejects a tampered batch_id
    // against the scoped option list, so a Livewire fillForm() cannot reach
    // BatchIntakeService at all with an out-of-scope id — that validation
    // error would prove Filament works, not that our authorization does.
    // The real threat named in the task is a caller that reaches the
    // service by a path that skips the form entirely, so this calls
    // BatchIntakeService::receive() directly, exactly as
    // BatchIntakeRateTest:108 does for the ManageCustomers guard.
    expect(fn () => app(BatchIntakeService::class)->receive($this->batch, [
        'customer_id' => $this->customer->id,
        'recipient_is_customer' => true,
        'packages' => [['weight_kg' => 2.0, 'description' => null]],
    ], $this->damascusEmployee))->toThrow(DomainException::class, 'خارج نطاق');

    expect(Shipment::count())->toBe(0);
});

test('an administrator can still receive into any open batch', function () {
    Livewire::actingAs($this->admin)
        ->test(ReceiveIntoBatch::class)
        ->assertFormFieldExists('batch_id', checkFieldUsing: fn ($field): bool => array_key_exists($this->batch->id, $field->getOptions()))
        ->fillForm([
            'batch_id' => $this->batch->id,
            'customer_id' => $this->customer->id,
            'recipient_is_customer' => true,
            'packages' => [['weight_kg' => 2.0, 'description' => null, 'pricing_method' => 'per_kg', 'custom_rate_per_kg' => '5.00']],
            'final_charge_usd' => '10.00',
        ])
        ->call('receive')
        ->assertHasNoFormErrors();

    expect(Shipment::where('batch_id', $this->batch->id)->count())->toBe(1);
});

test('a user with no warehouse assignment and no administrator role cannot open the page at all', function () {
    $unassigned = User::create([
        'name' => 'بلا مستودع', 'email' => 'unassigned@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => null,
    ]);

    expect(ReceiveIntoBatch::canAccess())->toBeFalse();

    $this->actingAs($unassigned);
    expect(ReceiveIntoBatch::canAccess())->toBeFalse();
});
