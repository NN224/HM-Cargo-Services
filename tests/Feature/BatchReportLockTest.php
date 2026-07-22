<?php

use App\Enums\BatchStatus;
use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Resources\Batches\Pages\BatchReport;
use App\Models\Batch;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * The batch report exposes profit, so it is gated on the pricing capability.
 * It had no guard at all — the figures were readable by any employee, while
 * the identical numbers were correctly hidden on the profitability widget.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $route = Route::create([
        'name' => 'Dubai → Syria',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $damascus->id,
    ]);

    $this->batch = Batch::create([
        'reference' => 'BCH-2026-0001',
        'route_id' => $route->id,
        'status' => BatchStatus::Dispatched,
        'cost_per_kg_cents' => 150,
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

test('an employee without the pricing capability sees the report locked, with no figures', function () {
    expect($this->clerk->hasCapability(Capability::PriceShipments))->toBeFalse();

    Livewire::actingAs($this->clerk)
        ->test(BatchReport::class, ['record' => $this->batch->getRouteKey()])
        ->assertSee('هذه الصفحة مقفلة')
        // None of the report's own section headings render.
        ->assertDontSee('إحصائيات تشغيلية')
        ->assertDontSee('الربح');
});

test('an administrator sees the real report', function () {
    Livewire::actingAs($this->admin)
        ->test(BatchReport::class, ['record' => $this->batch->getRouteKey()])
        ->assertDontSee('هذه الصفحة مقفلة')
        ->assertSee('الربح');
});

test('an employee who holds the pricing capability sees the real report', function () {
    $this->clerk->grantCapability(Capability::PriceShipments);

    Livewire::actingAs($this->clerk->fresh())
        ->test(BatchReport::class, ['record' => $this->batch->getRouteKey()])
        ->assertDontSee('هذه الصفحة مقفلة')
        ->assertSee('الربح');
});
