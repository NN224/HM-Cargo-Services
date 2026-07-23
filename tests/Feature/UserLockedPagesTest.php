<?php

use App\Enums\LockablePage;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\Warehouse;

/**
 * Locks stored on the user, mirroring how capabilities are stored: a list of
 * string keys, unrecognised values ignored, and never applying to an admin.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);

    $this->clerk = User::create([
        'name' => 'موظف', 'email' => 'clerk@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $dubai->id,
    ]);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);
});

test('a page can be locked and unlocked for an employee', function () {
    expect($this->clerk->isPageLocked(LockablePage::Payments))->toBeFalse();

    $this->clerk->lockPage(LockablePage::Payments);
    expect($this->clerk->fresh()->isPageLocked(LockablePage::Payments))->toBeTrue();

    $this->clerk->unlockPage(LockablePage::Payments);
    expect($this->clerk->fresh()->isPageLocked(LockablePage::Payments))->toBeFalse();
});

test('an administrator is never locked, whatever the column holds', function () {
    $this->admin->forceFill(['locked_pages' => [LockablePage::Payments->value]])->save();

    expect($this->admin->fresh()->isPageLocked(LockablePage::Payments))->toBeFalse();
});

test('an unrecognised stored key is ignored', function () {
    $this->clerk->forceFill(['locked_pages' => ['payments', 'not-a-real-page']])->save();

    expect($this->clerk->fresh()->lockedPageList())->toBe(['payments']);
});
