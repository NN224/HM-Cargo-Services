<?php

use App\Enums\UserRole;
use App\Filament\Resources\CustomerRates\Pages\EditCustomerRate;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $route = Route::create([
        'name' => 'Dubai → Syria',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $damascus->id,
    ]);

    $customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);

    $this->rate = CustomerRate::create([
        'customer_id' => $customer->id,
        'route_id' => $route->id,
        'rate_per_kg_cents' => 300,
    ]);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);
});

test('the save action requires confirmation', function () {
    // A typed rate is a standing agreement: a slipped decimal becomes the
    // customer's price until somebody notices. One click is enough to catch
    // it, and is not ceremony.
    //
    // Filament 5.7 has no `assertActionRequiresConfirmation()` helper (the
    // brief's suggested call). The save button is a schema-embedded action
    // living in the page's "content" schema (its footer), not a page-level
    // action, so it must be addressed via `TestAction::schemaComponent()`.
    // `mountAction()` calls a non-modal action immediately and unmounts it
    // (see InteractsWithActions::mountAction()/callMountedAction()), so an
    // action still mounted afterwards is proof the confirmation modal is
    // blocking it from running.
    Livewire::actingAs($this->admin)
        ->test(EditCustomerRate::class, ['record' => $this->rate->getRouteKey()])
        ->mountAction(TestAction::make('save')->schemaComponent(true, 'content'))
        ->assertActionMounted(TestAction::make('save')->schemaComponent(true, 'content'));
});

test('the confirmation modal names the old and new figures', function () {
    // The form's TextInput displays dollars and holds its live (pre-save)
    // state as a dollar string ("3.50"); dehydration to integer cents only
    // happens inside save()'s form->getState() call. So the modal description
    // — evaluated while the confirmation is open, before save() runs — must
    // read the pending value as dollars, not cents.
    Livewire::actingAs($this->admin)
        ->test(EditCustomerRate::class, ['record' => $this->rate->getRouteKey()])
        ->fillForm(['rate_per_kg_cents' => '3.50'])
        ->mountAction(TestAction::make('save')->schemaComponent(true, 'content'))
        ->assertActionMounted(TestAction::make('save')->schemaComponent(true, 'content'))
        ->assertMountedActionModalSee('3.00')
        ->assertMountedActionModalSee('3.50');
});

test('an invalid rate shows a validation error and never raises the confirmation modal', function () {
    // The confirmation modal is the LAST gate, not the first: an operator who
    // types an invalid rate must see the field error immediately, and must
    // never be asked to confirm a change that cannot be saved anyway. Before
    // the fix, ->action(fn () => $this->save()) only validates once the
    // modal is confirmed and callMountedAction() runs save() — so mounting
    // the action here would wrongly succeed (and show a modal) even though
    // 0 fails the field's minValue(0.01) rule.
    // ->assertHasFormErrors() defaults to the *mounted action's own* schema
    // once an action is mounted (see TestsForms::assertHasFormErrors()), and
    // the "save" action has no schema of its own — so the page's own form
    // schema must be named explicitly here, or the assertion resolves the
    // wrong (nonexistent) schema.
    Livewire::actingAs($this->admin)
        ->test(EditCustomerRate::class, ['record' => $this->rate->getRouteKey()])
        ->fillForm(['rate_per_kg_cents' => '0'])
        ->mountAction(TestAction::make('save')->schemaComponent(true, 'content'))
        ->assertHasFormErrors(['rate_per_kg_cents'], form: 'form')
        ->assertActionNotMounted(TestAction::make('save')->schemaComponent(true, 'content'));

    // The stored rate must be untouched — an invalid submission never saves.
    expect($this->rate->fresh()->rate_per_kg_cents)->toBe(300);
});

test('a confirmed change is saved', function () {
    // Exercise the actual UI path (mount → confirm → callMountedAction),
    // not a direct ->call('save'). The Save button's click handler was
    // rewired to go through the confirmation system (see EditCustomerRate),
    // so a test that bypasses it via a raw method call would prove save()
    // works in isolation without proving confirming actually persists.
    Livewire::actingAs($this->admin)
        ->test(EditCustomerRate::class, ['record' => $this->rate->getRouteKey()])
        ->fillForm(['rate_per_kg_cents' => '3.50'])
        ->mountAction(TestAction::make('save')->schemaComponent(true, 'content'))
        ->callMountedAction();

    expect($this->rate->fresh()->rate_per_kg_cents)->toBe(350);
});
