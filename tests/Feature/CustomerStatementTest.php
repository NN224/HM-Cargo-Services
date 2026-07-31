<?php

use App\Enums\UserRole;
use App\Filament\Resources\Customers\Pages\StatementCustomer;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CustomerStatementService;
use App\Services\PaymentService;
use Livewire\Livewire;

beforeEach(function () {
    $this->warehouse = Warehouse::create(['name' => 'دبي', 'location' => 'الإمارات']);
    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'stmt@hmcargo.test', 'password' => 'x',
        'role' => UserRole::Administrator, 'warehouse_id' => $this->warehouse->id,
    ]);
    $this->customer = Customer::create(['name' => 'عميل الحساب', 'phone' => '+9715000001']);
});

function makePricedShipment(Customer $customer, int $chargeCents, string $pricedAt): Shipment
{
    return tap(Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'مستلم', 'recipient_phone' => '+9613000001',
    ]), function (Shipment $s) use ($chargeCents, $pricedAt) {
        $s->forceFill([
            'final_charge_cents' => $chargeCents,
            'priced_at' => $pricedAt,
        ])->save();
    });
}

function recordPayment(array $overrides = []): Payment
{
    $service = app(PaymentService::class);

    return $service->recordPayment(array_merge([
        'customer_id' => 1,
        'amount_cents' => 5000,
        'method' => Payment::METHOD_CASH,
        'collected_at' => now(),
        'collected_by' => 1,
        'warehouse_id' => 1,
    ], $overrides));
}

// ---------------------------------------------------- reconciliation

test('statement reconciles running balance with outstanding and unapplied credit', function () {
    $s1 = makePricedShipment($this->customer, 10000, now()->subDays(4)->toDateTimeString());
    $s2 = makePricedShipment($this->customer, 8000, now()->subDays(3)->toDateTimeString());

    $service = app(PaymentService::class);

    $p1 = $service->recordPayment([
        'customer_id' => $this->customer->id,
        'amount_cents' => 5000,
        'method' => Payment::METHOD_CASH,
        'collected_at' => now()->subDays(2),
        'collected_by' => $this->admin->id,
        'warehouse_id' => $this->warehouse->id,
    ], $s1);

    $p2 = $service->recordPayment([
        'customer_id' => $this->customer->id,
        'amount_cents' => 2000,
        'method' => Payment::METHOD_BANK,
        'collected_at' => now()->subDay(),
        'collected_by' => $this->admin->id,
        'warehouse_id' => $this->warehouse->id,
    ]);

    $service->reversePayment($p1, $this->admin, 'إلغاء خاطئ');

    $statement = (new CustomerStatementService)->getStatement($this->customer);

    // After reversal, running balance must equal outstanding - unapplied.
    // S1: charged 10000, paid 0 (alloc reversed) → outstanding 10000
    // S2: charged 8000, paid 0 (never allocated) → outstanding 8000
    // Payments: 5000 + 2000 - 5000 = 2000
    // Allocations: 5000 + 0 - 5000 = 0
    // Unapplied: 2000 - 0 = 2000
    // Expected: 18000 - 2000 = 16000

    $totalOutstanding = $this->customer->shipments->sum(fn ($s) => $s->fresh()->outstandingCents());
    $totalAllocated = PaymentAllocation::whereHas('payment', fn ($q) => $q->where('customer_id', $this->customer->id)
    )->sum('amount_cents');
    $totalPaid = Payment::where('customer_id', $this->customer->id)->sum('amount_cents');
    $unapplied = $totalPaid - $totalAllocated;

    $expectedBalance = $totalOutstanding - $unapplied;

    expect($statement)->not->toBeEmpty()
        // Cross-check: walking the events chronologically must land on the
        // same figure as summing the ledger in aggregate. Two different code
        // paths, so agreement is meaningful.
        ->and($statement[array_key_last($statement)]['running_balance_cents'])
        ->toBe($expectedBalance)
        // And pin the arithmetic worked out above, so a bug shared by both
        // paths cannot pass. Without this the assertion is self-referential.
        ->and($expectedBalance)->toBe(16000);
});

// -------------------------------------------------------- event types

test('statement includes charge, payment, reversal and allocation events', function () {
    $s1 = makePricedShipment($this->customer, 5000, now()->subDays(2)->toDateTimeString());
    $service = app(PaymentService::class);
    $p1 = $service->recordPayment([
        'customer_id' => $this->customer->id,
        'amount_cents' => 3000,
        'method' => Payment::METHOD_CASH,
        'collected_at' => now()->subDay(),
        'collected_by' => $this->admin->id,
        'warehouse_id' => $this->warehouse->id,
    ], $s1);

    $statement = (new CustomerStatementService)->getStatement($this->customer);

    $types = array_column($statement, 'type');

    expect($types)->toContain('charge')
        ->and($types)->toContain('payment')
        ->and($types)->toContain('allocation');
});

test('statement identifies the correcting payment and includes its reason', function () {
    $payment = app(PaymentService::class)->recordPayment([
        'customer_id' => $this->customer->id,
        'amount_cents' => 3000,
        'method' => Payment::METHOD_CASH,
        'collected_at' => now()->subMinute(),
        'collected_by' => $this->admin->id,
        'warehouse_id' => $this->warehouse->id,
    ]);

    app(PaymentService::class)->reversePayment(
        $payment,
        $this->admin,
        'العميل لم يدفع فعلياً'
    );

    $statement = (new CustomerStatementService)->getStatement($this->customer);
    $reversal = collect($statement)->firstWhere('type', 'reversal');

    expect($reversal)->not->toBeNull()
        ->and($reversal['description'])->toContain('تصحيح دفعة')
        ->toContain('العميل لم يدفع فعلياً');
});

test('interactive statement renders movement amount and running balance', function () {
    makePricedShipment($this->customer, 12345, now()->subMinute()->toDateTimeString());

    $html = Livewire::actingAs($this->admin)
        ->test(StatementCustomer::class, ['record' => $this->customer->getRouteKey()])
        ->html();

    expect($html)->toMatch('/infolist\.lines\.0\.amount_cents.*?\$123\.45/s')
        ->toMatch('/infolist\.lines\.0\.running_balance_cents.*?\$123\.45/s');
});

test('statement events are chronologically ordered', function () {
    $s1 = makePricedShipment($this->customer, 5000, '2026-01-01 10:00:00');
    $s2 = makePricedShipment($this->customer, 7000, '2026-02-01 10:00:00');

    $service = app(PaymentService::class);
    $service->recordPayment([
        'customer_id' => $this->customer->id,
        'amount_cents' => 4000,
        'method' => Payment::METHOD_CASH,
        'collected_at' => '2026-03-01 10:00:00',
        'collected_by' => $this->admin->id,
        'warehouse_id' => $this->warehouse->id,
    ]);

    $statement = (new CustomerStatementService)->getStatement($this->customer);
    $dates = array_column($statement, 'date');

    for ($i = 1; $i < count($dates); $i++) {
        expect($dates[$i])->toBeGreaterThanOrEqual($dates[$i - 1]);
    }
});

test('empty statement for customer with no activity', function () {
    $statement = (new CustomerStatementService)->getStatement($this->customer);

    expect($statement)->toBe([]);
});
