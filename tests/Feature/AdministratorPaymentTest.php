<?php

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PaymentService;

/**
 * An administrator recording a payment.
 *
 * Administrators carry no warehouse assignment — that is what distinguishes
 * them from warehouse employees. The payment record, however, must name the
 * warehouse where the money changed hands (pricing-payments.md §7), so the
 * warehouse has to come from the form rather than from the user.
 *
 * This case had no test, and the whole suite stayed green while the most
 * common operator in the system could not record a payment at all.
 */
beforeEach(function () {
    $this->dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);

    $this->admin = User::create([
        'name' => 'المدير',
        'email' => 'admin@test.local',
        'password' => 'secret-for-test',
        'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);
});

test('an administrator has no warehouse of their own', function () {
    // The premise of the bug: nothing to copy onto the payment.
    expect($this->admin->warehouse_id)->toBeNull();
});

test('a payment records the warehouse it was collected at, chosen explicitly', function () {
    $payment = app(PaymentService::class)->recordPayment([
        'customer_id' => $this->customer->id,
        'amount_cents' => 5000,
        'method' => 'cash',
        'collected_at' => now(),
        'collected_by' => $this->admin->id,
        // Supplied by the form, not copied from the administrator.
        'warehouse_id' => $this->dubai->id,
    ]);

    expect($payment)->toBeInstanceOf(Payment::class)
        ->and($payment->warehouse_id)->toBe($this->dubai->id)
        ->and($payment->amount_cents)->toBe(5000);
});

test('a payment cannot be recorded without naming a warehouse', function () {
    // Refuse in the service with a clear message rather than letting the
    // database throw a constraint violation at the operator.
    expect(fn () => app(PaymentService::class)->recordPayment([
        'customer_id' => $this->customer->id,
        'amount_cents' => 5000,
        'method' => 'cash',
        'collected_at' => now(),
        'collected_by' => $this->admin->id,
        'warehouse_id' => null,
    ]))->toThrow(DomainException::class);
});
