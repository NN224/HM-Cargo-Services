<?php

use App\Enums\UserRole;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PaymentService;
use Livewire\Livewire;

/**
 * What the reversal action says to the person using it.
 *
 * "عكس الدفعة" is accounting vocabulary. The operator recording cash at a
 * counter is not an accountant, and the screen offered them a red button, a
 * blank "are you sure?", and a field asking for a reason — without once
 * saying what the action is for, that the original record survives it, or
 * that anything happened afterwards.
 *
 * The wording is the feature here. These assertions are about words on a
 * screen because that is what was broken.
 */
beforeEach(function () {
    $warehouse = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);

    $this->payment = app(PaymentService::class)->recordPayment([
        'customer_id' => $this->customer->id,
        'amount_cents' => 5000,
        'method' => Payment::METHOD_CASH,
        'collected_at' => now(),
        'collected_by' => $this->admin->id,
        'warehouse_id' => $warehouse->id,
    ]);
});

test('the action is named for what it is for, not for what it is called in bookkeeping', function () {
    Livewire::actingAs($this->admin)
        ->test(ListPayments::class)
        ->assertSee('تصحيح دفعة خاطئة');
});

test('the confirmation explains that the original payment survives', function () {
    // The one thing an operator most needs to know before clicking: this does
    // not erase anything. Without it the red button reads like a delete.
    //
    // Bound to the record rather than read bare, because the description is
    // built from it — the amount and the customer's name are the half of the
    // sentence that makes it concrete. Filament builds modal bodies when the
    // modal opens, so they are not in the page's initial HTML to assert on.
    $action = Livewire::actingAs($this->admin)
        ->test(ListPayments::class)
        ->instance()
        ->getTable()
        ->getAction('reverse')
        ->record($this->payment);

    expect((string) $action->getModalHeading())->toContain('تصحيح')
        ->and((string) $action->getModalDescription())
        ->toContain('لا تُحذف')
        ->toContain('أحمد')
        ->toContain('$50.00');
});

test('a completed correction says so', function () {
    // The action previously ran in silence: nothing on screen changed except
    // a new row, and only if you knew to look for it.
    Livewire::actingAs($this->admin)
        ->test(ListPayments::class)
        ->callTableAction('reverse', $this->payment->id, ['reason' => 'سُجّلت بالخطأ على العميل الغلط'])
        ->assertNotified();

    expect(Payment::where('reverses_payment_id', $this->payment->id)->exists())->toBeTrue();
});

test('the correcting row is labelled in words, not as a bare opposite', function () {
    app(PaymentService::class)->reversePayment(
        $this->payment,
        $this->admin,
        'سُجّلت بالخطأ'
    );

    Livewire::actingAs($this->admin)
        ->test(ListPayments::class)
        ->assertSee('تصحيح');
});
