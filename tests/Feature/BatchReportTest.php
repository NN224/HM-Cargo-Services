<?php

use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\BatchDispatchService;
use App\Services\BatchReportService;

beforeEach(function () {
    $this->dubai = Warehouse::create(['name' => 'دبي', 'location' => 'الإمارات']);
    $this->damascus = Warehouse::create(['name' => 'دمشق', 'location' => 'سوريا']);

    $this->route = Route::create([
        'name' => 'دبي ← دمشق',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'br@hmcargo.test', 'password' => 'x',
        'role' => UserRole::Administrator, 'warehouse_id' => $this->dubai->id,
    ]);

    $this->customer = Customer::create(['name' => 'عميل التقرير', 'phone' => '+9715000001']);
});

function makeBatchShipment(Batch $batch, Customer $customer, string $weight, int $charge, string $status, int $paid = 0): Shipment
{
    return tap(Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'مستلم', 'recipient_phone' => '+9613000001',
        'status' => $status,
    ]), function (Shipment $s) use ($batch, $weight, $charge, $paid) {
        $s->forceFill([
            'batch_id' => $batch->id,
            'total_weight_kg' => $weight,
            'final_charge_cents' => $charge,
            'paid_amount_cents' => $paid,
        ])->save();
    });
}

// ----------------------------------------------------------- report data

test('batch report shows shipment count, package count and total weight', function () {
    $this->actingAs($this->admin);
    $batch = Batch::create(['route_id' => $this->route->id]);
    $batch = app(BatchDispatchService::class)->dispatch($batch, 300);

    $s1 = makeBatchShipment($batch, $this->customer, '2.5000', 9000, ShipmentStatus::Pending->value, 5000);
    $s1->packages()->create(['weight_kg' => 1.5]);
    $s1->packages()->create(['weight_kg' => 1.0]);

    $s2 = makeBatchShipment($batch, $this->customer, '3.0000', 12000, ShipmentStatus::Pending->value, 3000);
    $s2->packages()->create(['weight_kg' => 3.0]);

    $s3 = makeBatchShipment($batch, $this->customer, '50.0000', 99999, ShipmentStatus::Cancelled->value);
    $s3->packages()->create(['weight_kg' => 50.0]);

    $report = (new BatchReportService)->getReport($batch);

    expect($report['shipment_count'])->toBe(3)
        ->and($report['package_count'])->toBe(4)
        ->and($report['total_weight_kg'])->toBe('5.5000');
});

test('batch report shows revenue, cost, collected, outstanding and profit', function () {
    $this->actingAs($this->admin);
    $batch = Batch::create(['route_id' => $this->route->id]);
    $batch = app(BatchDispatchService::class)->dispatch($batch, 450);

    makeBatchShipment($batch, $this->customer, '2.0000', 11000, ShipmentStatus::Pending->value, 6000);
    makeBatchShipment($batch, $this->customer, '3.0000', 9000, ShipmentStatus::Pending->value, 2000);

    $report = (new BatchReportService)->getReport($batch);

    expect($report['revenue_cents'])->toBe(20000)
        ->and($report['collected_cents'])->toBe(8000)
        ->and($report['outstanding_cents'])->toBe(12000)
        ->and($report['cost_cents'])->not->toBeNull()
        ->and($report['profit_cents'])->not->toBeNull();
});

test('batch report shows unavailable when cost not set', function () {
    $batch = Batch::create(['route_id' => $this->route->id]);
    makeBatchShipment($batch, $this->customer, '2.0000', 5000, ShipmentStatus::Pending->value);

    $report = (new BatchReportService)->getReport($batch);

    expect($report['cost_cents'])->toBeNull()
        ->and($report['profit_cents'])->toBeNull()
        ->and($report['revenue_cents'])->toBe(5000);
});

test('batch report excludes cancelled shipments from financials', function () {
    $this->actingAs($this->admin);
    $batch = Batch::create(['route_id' => $this->route->id]);
    $batch = app(BatchDispatchService::class)->dispatch($batch, 200);

    makeBatchShipment($batch, $this->customer, '5.0000', 20000, ShipmentStatus::Pending->value, 15000);
    makeBatchShipment($batch, $this->customer, '99.0000', 99999, ShipmentStatus::Cancelled->value);

    $report = (new BatchReportService)->getReport($batch);

    // Revenue: 20000 (active only)
    expect($report['revenue_cents'])->toBe(20000);
});
