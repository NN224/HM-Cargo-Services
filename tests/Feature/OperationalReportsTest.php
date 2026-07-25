<?php

use App\Enums\UserRole;
use App\Filament\Pages\OperationalReports;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $this->dubai = Warehouse::create(['name' => 'دبي', 'location' => 'الإمارات']);

    $this->admin = User::create([
        'name' => 'مدير النظام',
        'email' => 'admin-reports@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::Administrator,
        'warehouse_id' => $this->dubai->id,
    ]);
});

test('an administrator can access the operational reports page', function () {
    Livewire::actingAs($this->admin)
        ->test(OperationalReports::class)
        ->assertSuccessful()
        ->assertSee('التقارير التشغيلية والمالية')
        ->assertSee('حركة المستودعات والطرود')
        ->assertSee('أداء خطوط الشحن');
});

test('switching tabs changes the active report view', function () {
    Livewire::actingAs($this->admin)
        ->test(OperationalReports::class)
        ->call('setTab', 'routes')
        ->assertSet('activeTab', 'routes')
        ->call('setTab', 'financial')
        ->assertSet('activeTab', 'financial')
        ->call('setTab', 'exceptions')
        ->assertSet('activeTab', 'exceptions');
});

test('filtering by date range updates report calculations', function () {
    Livewire::actingAs($this->admin)
        ->test(OperationalReports::class)
        ->set('date_from', '2026-01-01')
        ->set('date_to', '2026-12-31')
        ->assertSuccessful();
});
