<?php

use App\Enums\UserRole;
use App\Filament\Resources\Batches\Pages\ListBatches;
use App\Filament\Resources\Routes\Pages\ListRoutes;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * What an empty screen tells the operator.
 *
 * On a fresh install every list is empty, and an empty table renders as a
 * header and "no records" — every screen looks like every other screen. The
 * batches screen is worse than uninformative: its route field is required and
 * its options come from a table that is also empty, so the create form cannot
 * be satisfied and the operator is left clicking a button that leads nowhere.
 *
 * A first user read this as "batches and routes are the same thing, except
 * batches won't let me add." Nothing on screen contradicted them.
 */
beforeEach(function () {
    $this->dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);
});

test('with no routes, the batches screen says a route is needed first', function () {
    Livewire::actingAs($this->admin)
        ->test(ListBatches::class)
        ->assertSee('لا يوجد أي مسار بعد')
        ->assertSee('أنشئ مساراً');
});

test('with routes present, the batches screen invites a batch instead', function () {
    Route::create([
        'name' => 'Dubai → Syria',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);

    // The prerequisite is satisfied, so the guidance must change — otherwise
    // it becomes noise the operator learns to ignore.
    Livewire::actingAs($this->admin)
        ->test(ListBatches::class)
        ->assertDontSee('لا يوجد أي مسار بعد')
        ->assertSee('لا توجد رحلات بعد');
});

test('the routes screen explains what a route is for', function () {
    Livewire::actingAs($this->admin)
        ->test(ListRoutes::class)
        ->assertSee('لا توجد مسارات بعد');
});
