<?php

use App\Enums\LockablePage;
use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * The administrator's controls for locking pages per employee, below the
 * existing capability toggles.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);

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

    $this->actingAs($this->admin);
});

test('the form shows a lock toggle for every registry page and none for the dashboard', function () {
    $component = Livewire::test(EditUser::class, ['record' => $this->clerk->getRouteKey()]);

    foreach (LockablePage::cases() as $page) {
        $component->assertFormFieldExists("lock_{$page->value}");
    }

    $component->assertFormFieldDoesNotExist('lock_dashboard');
});

test('toggling a lock on stores it, and off removes it', function () {
    Livewire::test(EditUser::class, ['record' => $this->clerk->getRouteKey()])
        ->fillForm(['lock_payments' => true])
        ->call('save');

    expect($this->clerk->fresh()->isPageLocked(LockablePage::Payments))->toBeTrue();

    Livewire::test(EditUser::class, ['record' => $this->clerk->getRouteKey()])
        ->fillForm(['lock_payments' => false])
        ->call('save');

    expect($this->clerk->fresh()->isPageLocked(LockablePage::Payments))->toBeFalse();
});
