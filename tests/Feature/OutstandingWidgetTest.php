<?php

use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Widgets\OutstandingWidget;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CustomerStatementService;
use App\Services\PaymentService;
use Livewire\Livewire;

/**
 * The outstanding total is money, so it is gated on the payments capability
 * and must equal what the customer statements reconcile to — the dashboard
 * and the statements cannot be allowed to disagree.
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

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);

    // A shipment charged $100, of which $30 has been paid → $70 outstanding.
    $shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'سامي',
        'recipient_phone' => '+9613000001',
        'destination_warehouse_id' => $damascus->id,
    ]);
    $shipment->forceFill(['final_charge_cents' => 10000])->save();

    app(PaymentService::class)->recordPayment([
        'customer_id' => $this->customer->id,
        'amount_cents' => 3000,
        'method' => Payment::METHOD_CASH,
        'collected_at' => now(),
        'collected_by' => $this->admin->id,
        'warehouse_id' => $this->warehouse->id,
    ], $shipment);
});

test('the outstanding widget is hidden from an employee without the payments capability', function () {
    $this->actingAs($this->clerk);
    expect(OutstandingWidget::canView())->toBeFalse();
});

test('it is shown to an administrator and to an employee holding the capability', function () {
    $this->actingAs($this->admin);
    expect(OutstandingWidget::canView())->toBeTrue();

    $this->clerk->grantCapability(Capability::RecordPayments);
    $this->actingAs($this->clerk->fresh());
    expect(OutstandingWidget::canView())->toBeTrue();
});

test('the figure is the outstanding total and reconciles with the statement', function () {
    Livewire::actingAs($this->admin)
        ->test(OutstandingWidget::class)
        ->assertSee('المستحق')
        ->assertSee('$70.00');

    // The dashboard total must equal the sum the statements reconcile to.
    $statementOutstanding = app(CustomerStatementService::class)
        ->getSummary($this->customer)['outstanding_cents'];

    expect($statementOutstanding)->toBe(7000);
});
