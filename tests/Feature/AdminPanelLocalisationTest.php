<?php

use App\Enums\UserRole;
use App\Filament\Resources\CustomerRates\Pages\ListCustomerRates;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * The admin UI is Arabic-first and RTL (AGENTS.md technical direction).
 *
 * These guard against a regression that already happened once: three
 * resources shipped with English navigation and column headers, and the raw
 * column name "Rate per kg cents" leaked the internal storage unit into the UI.
 */
beforeEach(function () {
    $warehouse = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => $warehouse->id,
    ]);
});

test('every resource shows an Arabic navigation label', function (string $url, string $label) {
    $this->actingAs($this->admin)
        ->get($url)
        ->assertOk()
        ->assertSee($label, escape: false);
})->with([
    ['/admin/customers', 'العملاء'],
    ['/admin/routes', 'المسارات'],
    ['/admin/customer-rates', 'أسعار العملاء'],
    ['/admin/warehouses', 'المستودعات'],
]);

test('the rates table is labelled in Arabic and never shows the cents unit', function () {
    // Filament renders tables lazily through Livewire, so the column headers
    // are absent from the initial page HTML. The component itself must be
    // rendered to see them.
    $this->actingAs($this->admin);

    Livewire::test(ListCustomerRates::class)
        ->assertSee('السعر لكل كيلوغرام', escape: false)
        ->assertSee('العميل', escape: false)
        ->assertSee('المسار', escape: false)
        // The humanised column name is what would leak the storage unit to an
        // operator. The raw snake_case name legitimately appears in Livewire's
        // internal state and is not user-visible, so it is not asserted here.
        ->assertDontSee('Rate per kg cents');
});

test('the panel renders right to left', function () {
    $this->actingAs($this->admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('dir="rtl"', escape: false);
});
