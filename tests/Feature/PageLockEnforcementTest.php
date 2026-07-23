<?php

use App\Enums\Capability;
use App\Enums\LockablePage;
use App\Enums\UserRole;
use App\Filament\Pages\PageLocked;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Shipments\ShipmentResource;
use App\Models\User;
use App\Models\Warehouse;

/**
 * Central enforcement: one middleware, driven by the registry, redirects a
 * locked employee to the locked page — for every lockable route, with no
 * per-page code.
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

test('an employee with a page locked is redirected to the locked page', function () {
    $this->clerk->lockPage(LockablePage::Shipments);

    $this->actingAs($this->clerk)
        ->get(ShipmentResource::getUrl('index'))
        ->assertRedirect(PageLocked::getUrl());
});

test('the locked page tells the employee an administrator locked it', function () {
    $this->actingAs($this->clerk)
        ->get(PageLocked::getUrl())
        ->assertSee('من الإدارة');
});

test('the same employee reaches a page that is not locked', function () {
    $this->clerk->lockPage(LockablePage::Shipments);

    // Shipments is locked; customers is not.
    $this->actingAs($this->clerk)
        ->get(CustomerResource::getUrl('index'))
        ->assertSuccessful();
});

test('a lock overrides a held capability but does not remove it', function () {
    $this->clerk->grantCapability(Capability::RecordPayments);
    $this->clerk->lockPage(LockablePage::Payments);

    // The payments page is blocked despite the capability...
    $this->actingAs($this->clerk->fresh())
        ->get(PaymentResource::getUrl('index'))
        ->assertRedirect(PageLocked::getUrl());

    // ...but the capability itself is intact.
    expect($this->clerk->fresh()->hasCapability(Capability::RecordPayments))->toBeTrue();
});

test('an administrator is never redirected, even with a key toggled on', function () {
    $this->admin->forceFill(['locked_pages' => [LockablePage::Shipments->value]])->save();

    $this->actingAs($this->admin->fresh())
        ->get(ShipmentResource::getUrl('index'))
        ->assertSuccessful();
});

test('every lockable page is enforced by the middleware', function () {
    // Walk the whole registry: each key, when locked, must redirect. This is
    // what stops a destination being lockable in the UI yet unenforced.
    //
    // The same employee is reused and re-locked each iteration rather than
    // creating a fresh user per key. A fresh user gets a fresh bcrypt salt
    // (the 'hashed' cast on User::password), and swapping actingAs() between
    // differently-salted users mid-test defeats Filament's AuthenticateSession
    // middleware — it treats the salt change as session hijacking and logs the
    // request out before it ever reaches EnforcePageLocks. That is the correct
    // security behaviour for that middleware; reusing one user keeps it happy
    // while still exercising the full production middleware stack.
    foreach (LockablePage::cases() as $page) {
        $this->clerk->forceFill(['locked_pages' => [$page->value]])->save();

        // The two custom Pages vs. everything else (resources). Matched by
        // exact key, not a substring — 'batches' contains 'batch' but is a
        // resource, not a page.
        $isPage = in_array($page->value, ['receive-into-batch', 'scan-packages'], true);
        $routeName = $isPage
            ? 'filament.admin.pages.'.$page->value
            : 'filament.admin.resources.'.$page->value.'.index';

        $this->actingAs($this->clerk->fresh())
            ->get(route($routeName))
            ->assertRedirect(PageLocked::getUrl());
    }
});
