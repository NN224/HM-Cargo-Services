<?php

use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Widgets\CustomersInDebtWidget;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PaymentService;
use Livewire\Livewire;

/**
 * The follow-up-for-payment list, gated on the payments capability. A customer
 * appears only while they owe something, largest first.
 */
beforeEach(function () {
    $this->warehouse = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);

    $this->clerk = User::create([
        'name' => 'موظف', 'email' => 'clerk@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $this->warehouse->id,
    ]);

    // In debt: charged $50, paid nothing.
    $this->inDebt = Customer::create(['name' => 'مدين', 'phone' => '+971500000001']);
    $s = Shipment::create([
        'customer_id' => $this->inDebt->id,
        'recipient_name' => 'س', 'recipient_phone' => '+9613000001',
        'destination_warehouse_id' => $damascus->id,
    ]);
    $s->forceFill(['final_charge_cents' => 5000])->save();

    // Settled: charged $20, paid $20.
    $this->settled = Customer::create(['name' => 'مسدّد', 'phone' => '+971500000002']);
    $s2 = Shipment::create([
        'customer_id' => $this->settled->id,
        'recipient_name' => 'ص', 'recipient_phone' => '+9613000002',
        'destination_warehouse_id' => $damascus->id,
    ]);
    $s2->forceFill(['final_charge_cents' => 2000])->save();
    app(PaymentService::class)->recordPayment([
        'customer_id' => $this->settled->id,
        'amount_cents' => 2000,
        'method' => Payment::METHOD_CASH,
        'collected_at' => now(),
        'collected_by' => $this->admin->id,
        'warehouse_id' => $this->warehouse->id,
    ], $s2);
});

test('the list is hidden from an employee without the payments capability', function () {
    $this->actingAs($this->clerk);
    expect(CustomersInDebtWidget::canView())->toBeFalse();
});

test('it shows a customer who owes, and its figure, and hides one who is settled', function () {
    Livewire::actingAs($this->admin)
        ->test(CustomersInDebtWidget::class)
        ->assertSee('مدين')
        ->assertSee('$50.00')
        ->assertDontSee('مسدّد');
});

test('it orders debtors by outstanding balance, largest first', function () {
    $damascus = Warehouse::where('name', 'Damascus')->firstOrFail();

    // Created first but owes less ($30, paid nothing) — if the DESC ordering
    // were dropped, insertion order would put this before the bigger debtor.
    $smallerDebtor = Customer::create(['name' => 'مدين أصغر', 'phone' => '+971500000004']);
    $s4 = Shipment::create([
        'customer_id' => $smallerDebtor->id,
        'recipient_name' => 'غ', 'recipient_phone' => '+9613000004',
        'destination_warehouse_id' => $damascus->id,
    ]);
    $s4->forceFill(['final_charge_cents' => 3000])->save();

    // Created second but owes more ($80, paid nothing).
    $biggerDebtor = Customer::create(['name' => 'مدين أكبر', 'phone' => '+971500000003']);
    $s3 = Shipment::create([
        'customer_id' => $biggerDebtor->id,
        'recipient_name' => 'ع', 'recipient_phone' => '+9613000003',
        'destination_warehouse_id' => $damascus->id,
    ]);
    $s3->forceFill(['final_charge_cents' => 8000])->save();

    Livewire::actingAs($this->admin)
        ->test(CustomersInDebtWidget::class)
        ->assertCanSeeTableRecords([$biggerDebtor, $smallerDebtor], inOrder: true);
});
