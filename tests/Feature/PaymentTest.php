<?php

namespace Tests\Feature;

use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function createCustomer(): Customer
    {
        return Customer::create([
            'name' => 'Test Customer',
            'phone' => '+971501234567',
        ]);
    }

    private function createShipment(Customer $customer, int $finalChargeCents, string $pricedAt): Shipment
    {
        $shipment = Shipment::create([
            'customer_id' => $customer->id,
            'recipient_name' => 'Recipient',
            'recipient_phone' => '+971500000000',
        ]);

        $shipment->forceFill([
            'final_charge_cents' => $finalChargeCents,
            'paid_amount_cents' => 0,
            'priced_at' => $pricedAt,
        ])->save();

        return $shipment;
    }

    public function test_payment_resource_access_controlled_by_capability(): void
    {
        $warehouse = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);

        $employeeWithoutCapability = User::factory()->create([
            'role' => UserRole::WarehouseEmployee,
            'warehouse_id' => $warehouse->id,
        ]);

        $employeeWithCapability = User::factory()->create([
            'role' => UserRole::WarehouseEmployee,
            'warehouse_id' => $warehouse->id,
            'capabilities' => [Capability::RecordPayments->value],
        ]);

        // The page is reachable by any authenticated user now — it renders
        // itself locked rather than 403ing, so an employee learns payments
        // exist and are not theirs instead of finding the screen missing.
        // The capability still gates whether the real list (or a locked
        // notice) is what renders; PaymentsLockTest covers that content.
        $this->actingAs($employeeWithoutCapability)
            ->get(PaymentResource::getUrl('index'))
            ->assertSuccessful();

        $this->actingAs($employeeWithCapability)
            ->get(PaymentResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_can_record_account_level_payment_and_allocate_fifo(): void
    {
        $warehouse = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'warehouse_id' => $warehouse->id]);
        $customer = $this->createCustomer();

        $shipment1 = $this->createShipment($customer, 1000, now()->subDays(2)->toDateTimeString()); // $10
        $shipment2 = $this->createShipment($customer, 2000, now()->subDay()->toDateTimeString()); // $20
        $shipment3 = $this->createShipment($customer, 1500, now()->toDateTimeString()); // $15

        Livewire::actingAs($admin)
            ->test(CreatePayment::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'amount' => 15.00, // $15.00 should fully pay shipment1, and partially pay shipment2
                'method' => Payment::METHOD_CASH,
                'collected_at' => now()->toDateTimeString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('payments', [
            'customer_id' => $customer->id,
            'amount_cents' => 1500,
            'method' => Payment::METHOD_CASH,
            'type' => Payment::TYPE_PAYMENT,
        ]);

        $payment = Payment::first();

        // Check allocations
        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'shipment_id' => $shipment1->id,
            'amount_cents' => 1000,
        ]);

        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'shipment_id' => $shipment2->id,
            'amount_cents' => 500,
        ]);

        // Check shipments paid amount
        $this->assertEquals(1000, $shipment1->fresh()->paid_amount_cents);
        $this->assertEquals(500, $shipment2->fresh()->paid_amount_cents);
        $this->assertEquals(0, $shipment3->fresh()->paid_amount_cents);
    }

    public function test_can_record_shipment_specific_payment(): void
    {
        $warehouse = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'warehouse_id' => $warehouse->id]);
        $customer = $this->createCustomer();

        $shipment1 = $this->createShipment($customer, 1000, now()->subDays(2)->toDateTimeString());
        $shipment2 = $this->createShipment($customer, 2000, now()->subDay()->toDateTimeString());

        Livewire::actingAs($admin)
            ->test(CreatePayment::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'shipment_id' => $shipment2->id,
                'amount' => 20.00,
                'method' => Payment::METHOD_WHISH,
                'collected_at' => now()->toDateTimeString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $payment = Payment::first();

        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'shipment_id' => $shipment2->id,
            'amount_cents' => 2000,
        ]);

        $this->assertEquals(0, $shipment1->fresh()->paid_amount_cents);
        $this->assertEquals(2000, $shipment2->fresh()->paid_amount_cents);
    }

    public function test_can_reverse_payment_as_administrator(): void
    {
        $warehouse = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
        $admin = User::factory()->create(['role' => UserRole::Administrator, 'warehouse_id' => $warehouse->id]);
        $customer = $this->createCustomer();
        $shipment = $this->createShipment($customer, 2000, now()->toDateTimeString());

        $service = app(PaymentService::class);
        $payment = $service->recordPayment([
            'customer_id' => $customer->id,
            'amount_cents' => 2000,
            'method' => Payment::METHOD_CASH,
            'collected_at' => now(),
            'collected_by' => $admin->id,
            'warehouse_id' => $warehouse->id,
        ], $shipment);

        $this->assertEquals(2000, $shipment->fresh()->paid_amount_cents);

        // Reverse
        Livewire::actingAs($admin)
            ->test(ListPayments::class)
            ->callTableAction('reverse', $payment, data: [
                'reason' => 'Customer requested refund',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('payments', [
            'type' => Payment::TYPE_REVERSAL,
            'reverses_payment_id' => $payment->id,
            'reversal_reason' => 'Customer requested refund',
            'amount_cents' => -2000,
        ]);

        $this->assertEquals(0, $shipment->fresh()->paid_amount_cents);
    }

    public function test_employee_cannot_reverse_payment(): void
    {
        $warehouse = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
        $employee = User::factory()->create([
            'role' => UserRole::WarehouseEmployee,
            'warehouse_id' => $warehouse->id,
            'capabilities' => [Capability::RecordPayments->value],
        ]);
        $customer = $this->createCustomer();

        $service = app(PaymentService::class);
        $payment = $service->recordPayment([
            'customer_id' => $customer->id,
            'amount_cents' => 1000,
            'method' => Payment::METHOD_CASH,
            'collected_at' => now(),
            'collected_by' => $employee->id,
            'warehouse_id' => $warehouse->id,
        ]);

        Livewire::actingAs($employee)
            ->test(ListPayments::class)
            ->assertTableActionHidden('reverse', $payment);
    }
}
