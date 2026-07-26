<?php

use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Pages\ReceiveIntoBatch;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);

    $this->clerk = User::create([
        'name' => 'موظف', 'email' => 'clerk@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $dubai->id,
    ]);
});

test('an employee holding manage_customers is offered customer creation', function () {
    $this->clerk->grantCapability(Capability::ManageCustomers);

    // Filament 5.7's getCreateOptionActionForm() requires a Schema argument
    // and hasCreateOptionActionFormSchema() only reports whether a schema
    // closure was ever attached (always true here) — neither distinguishes
    // an authorized user from an unauthorized one. The action itself carries
    // the real, backend-enforced ->authorize() gate (Filament re-checks it
    // on every mount/call, not just for rendering), so isVisible() on that
    // action is what actually reflects whether creation is offered.
    Livewire::actingAs($this->clerk->fresh())
        ->test(ReceiveIntoBatch::class)
        ->assertFormFieldExists('customer_id', checkFieldUsing: fn ($field): bool => $field->getCreateOptionAction()?->isVisible() ?? false);
});

test('an employee without it is not', function () {
    expect($this->clerk->hasCapability(Capability::ManageCustomers))->toBeFalse();

    Livewire::actingAs($this->clerk)
        ->test(ReceiveIntoBatch::class)
        ->assertFormFieldExists('customer_id', checkFieldUsing: fn ($field): bool => ! ($field->getCreateOptionAction()?->isVisible() ?? false));
});
