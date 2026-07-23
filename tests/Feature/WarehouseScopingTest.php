<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Warehouse;

/**
 * Warehouse scoping is the version 1 authorization boundary (D-003).
 * These tests guard the rule that an employee sees and acts on exactly one
 * warehouse, while an administrator is unrestricted.
 */
beforeEach(function () {
    $this->dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->beirut = Warehouse::create(['name' => 'Beirut', 'location' => 'Lebanon']);

    $this->admin = User::create([
        'name' => 'Admin', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
    ]);

    $this->employee = User::create([
        'name' => 'Dubai Clerk', 'email' => 'clerk@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $this->dubai->id,
    ]);
});

test('an employee sees only their assigned warehouse', function () {
    $visible = Warehouse::visibleTo($this->employee)->pluck('name');

    expect($visible)->toHaveCount(1)
        ->and($visible->first())->toBe('Dubai');
});

test('an administrator sees every warehouse', function () {
    expect(Warehouse::visibleTo($this->admin)->count())->toBe(2);
});

test('an employee cannot view a warehouse they are not assigned to', function () {
    expect($this->employee->can('view', $this->beirut))->toBeFalse()
        ->and($this->employee->can('view', $this->dubai))->toBeTrue();
});

test('an employee with no warehouse assignment sees nothing', function () {
    $unassigned = User::create([
        'name' => 'Floating', 'email' => 'floating@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
    ]);

    expect(Warehouse::visibleTo($unassigned)->count())->toBe(0)
        ->and($unassigned->can('view', $this->dubai))->toBeFalse();
});

test('only administrators may create or update warehouses', function () {
    expect($this->admin->can('create', Warehouse::class))->toBeTrue()
        ->and($this->employee->can('create', Warehouse::class))->toBeFalse()
        ->and($this->admin->can('update', $this->dubai))->toBeTrue()
        ->and($this->employee->can('update', $this->dubai))->toBeFalse();
});

test('warehouses can never be hard deleted, not even by an administrator', function () {
    expect($this->admin->can('delete', $this->dubai))->toBeFalse();
});

test('a deactivated user cannot access the admin panel', function () {
    $panel = Filament\Facades\Filament::getPanel('admin');

    expect($this->admin->canAccessPanel($panel))->toBeTrue();

    $this->admin->update(['is_active' => false]);

    expect($this->admin->fresh()->canAccessPanel($panel))->toBeFalse();
});
