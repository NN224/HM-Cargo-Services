<?php

use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * Payments are a whole money destination, so an employee without the payments
 * capability meets it locked rather than finding it missing — they should know
 * it exists and is not theirs, not wonder why a screen vanished.
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
});

test('payments stay in the navigation for an employee without the capability', function () {
    // Present, not absent: shouldRegisterNavigation no longer depends on the
    // capability, and canViewAny no longer 403s the page.
    $this->actingAs($this->clerk);

    expect(PaymentResource::shouldRegisterNavigation())->toBeTrue()
        ->and(PaymentResource::canViewAny())->toBeTrue();
});

test('opening payments without the capability shows the lock, not the table', function () {
    Livewire::actingAs($this->clerk)
        ->test(ListPayments::class)
        ->assertSee('هذه الصفحة مقفلة')
        ->assertDontSee('رقم الإيصال');
});

test('an employee who holds the capability sees the real payments list', function () {
    $this->clerk->grantCapability(Capability::RecordPayments);

    Livewire::actingAs($this->clerk->fresh())
        ->test(ListPayments::class)
        ->assertDontSee('هذه الصفحة مقفلة')
        ->assertSee('رقم الإيصال');
});

test('an administrator sees the real payments list', function () {
    Livewire::actingAs($this->admin)
        ->test(ListPayments::class)
        ->assertDontSee('هذه الصفحة مقفلة')
        ->assertSee('رقم الإيصال');
});
