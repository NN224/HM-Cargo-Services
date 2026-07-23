<?php

use App\Enums\UserRole;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Models\Customer;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * Guards a bug that shipped once: with APP_LOCALE=ar and no lang/ar files,
 * Laravel fell through to the raw translation key and operators were shown
 * "validation.regex" instead of a message.
 */
beforeEach(function () {
    $warehouse = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => $warehouse->id,
    ]);

    $this->actingAs($this->admin);
});

test('validation messages are translated, never raw keys', function () {
    Livewire::test(CreateCustomer::class)
        ->fillForm(['name' => 'nabel', 'phone' => 'alchaar'])
        ->call('create')
        ->assertHasFormErrors(['phone'])
        // The exact failure seen in the UI: the key leaked instead of a message.
        ->assertDontSee('validation.regex')
        ->assertDontSee('validation.unique')
        ->assertDontSee('validation.required');
});

test('the arabic validation catalogue is present and resolves', function () {
    expect(__('validation.regex'))->not->toBe('validation.regex')
        ->and(__('validation.required'))->not->toBe('validation.required')
        ->and(app()->getLocale())->toBe('ar');
});

test('a well formed international phone number is accepted', function () {
    Livewire::test(CreateCustomer::class)
        ->fillForm(['name' => 'نبيل', 'phone' => '+971501234567'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Customer::where('phone', '+971501234567')->exists())->toBeTrue();
});

test('a phone number containing letters is rejected', function () {
    Livewire::test(CreateCustomer::class)
        ->fillForm(['name' => 'nabel', 'phone' => 'alchaar'])
        ->call('create')
        ->assertHasFormErrors(['phone']);

    expect(Customer::count())->toBe(0);
});
