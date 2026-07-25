<?php

use App\Enums\BatchStatus;
use App\Enums\Capability;
use App\Enums\PackageStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\BatchAssignmentService;
use App\Services\BatchDispatchService;
use App\Services\BatchIntakeService;
use App\Services\CustomerStatementService;
use App\Services\PackageScanService;
use App\Services\PaymentService;
use App\Services\ShipmentCollectionService;
use App\Services\WhatsAppMessageService;

test('e2e full operational daily routine verification test', function () {
    // 1. MASTER DATA SETUP
    $originWh = Warehouse::firstOrCreate(['name' => 'مستودع دبي التجريبي'], ['location' => 'دبي', 'is_active' => true]);
    $transitWh = Warehouse::firstOrCreate(['name' => 'مستودع بيروت التجريبي'], ['location' => 'بيروت', 'is_active' => true]);
    $destWh = Warehouse::firstOrCreate(['name' => 'مستودع دمشق التجريبي'], ['location' => 'دمشق', 'is_active' => true]);

    $route = Route::firstOrCreate(['name' => 'دبي - بيروت - دمشق E2E'], [
        'origin_warehouse_id' => $originWh->id,
        'destination_warehouse_id' => $destWh->id,
        'transit_warehouse_id' => $transitWh->id,
        'rate_per_kg_cents' => 500, // $5.00/kg default
        'is_active' => true,
    ]);

    $customer = Customer::create([
        'name' => 'أحمد العلي E2E',
        'phone' => '+971501234567',
        'is_credit_customer' => true,
        'is_active' => true,
    ]);

    CustomerRate::create([
        'customer_id' => $customer->id,
        'route_id' => $route->id,
        'rate_per_kg_cents' => 450, // $4.50/kg special agreed rate
    ]);

    $admin = User::factory()->create([
        'role' => UserRole::Administrator,
        'warehouse_id' => $originWh->id,
        'capabilities' => Capability::cases(),
    ]);

    auth()->login($admin);

    $agreedRate = CustomerRate::where('customer_id', $customer->id)->where('route_id', $route->id)->first()?->rate_per_kg_cents;
    expect($agreedRate)->toBe(450);

    // 2. INTAKE & BATCH CREATION
    $batch = Batch::create([
        'reference' => 'BTH-E2E-001',
        'route_id' => $route->id,
        'status' => BatchStatus::Open,
        'capacity_kg' => 1000,
        'created_by' => $admin->id,
    ]);

    $intakeService = app(BatchIntakeService::class);
    $shipment = $intakeService->receive($batch, [
        'customer_id' => $customer->id,
        'recipient_is_customer' => false,
        'recipient_name' => 'سامر العلي',
        'recipient_phone' => '+963912345678',
        'packages' => [
            ['weight_kg' => 5.45, 'description' => 'ملابس'],
            ['weight_kg' => 3.20, 'description' => 'أحذية'],
        ],
    ], $admin);

    $shipment->refresh();
    expect((float) $shipment->total_weight_kg)->toBe(8.65);
    expect($shipment->packages)->toHaveCount(2);

    // Check pricing: 8.65 kg * $4.50 = $38.925 -> 3893 cents
    expect($shipment->final_charge_cents)->toBe(3893);
    expect($shipment->public_token)->not()->toBeEmpty();

    // 3. WHATSAPP & LABELS
    $waService = app(WhatsAppMessageService::class);
    $intakeMsg = $waService->intakeMessage($shipment);
    expect($intakeMsg)->toContain($shipment->public_token);

    // 4. BATCH DISPATCH
    $dispatchService = app(BatchDispatchService::class);
    $dispatchService->dispatch($batch, 200);

    expect($batch->fresh()->status)->toBe(BatchStatus::Dispatched);
    expect($shipment->fresh()->rate_per_kg_cents)->toBe(450);

    // 5. WAREHOUSE SCANNING AT TRANSIT & DESTINATION
    $scanService = app(PackageScanService::class);
    $pkg1 = $shipment->packages->first();
    $pkg2 = $shipment->packages->last();

    // First scan at transit warehouse (Arrived Transit)
    $scanService->scan($pkg1->barcode, $transitWh, $admin);
    $scanService->scan($pkg2->barcode, $transitWh, $admin);

    // Depart from transit
    $pkg1->update(['status' => PackageStatus::DepartedTransit]);
    $pkg2->update(['status' => PackageStatus::DepartedTransit]);

    // Now scan pkg1 at destination warehouse (Damascus)
    $scanService->scan($pkg1->barcode, $destWh, $admin);
    $pkg1->refresh();
    expect($pkg1->status)->toBe(PackageStatus::ArrivedDestination);

    // COLLECTION GATE CHECK: Can't collect before all packages arrive!
    $collectionService = app(ShipmentCollectionService::class);
    expect(fn () => $collectionService->collect($shipment, $destWh, $admin))->toThrow(DomainException::class);

    // Scan second package at destination warehouse
    $scanService->scan($pkg2->barcode, $destWh, $admin);
    $pkg2->refresh();
    expect($pkg2->status)->toBe(PackageStatus::ArrivedDestination);

    // 6. COLLECTION & MANUAL ROUNDING
    $collected = $collectionService->collect($shipment, $destWh, $admin);
    expect($collected->status)->toBe(ShipmentStatus::Collected);

    $paymentService = app(PaymentService::class);
    $paymentService->recordPayment([
        'customer_id' => $customer->id,
        'amount_cents' => 3800, // Customer pays $38.00
        'method' => 'cash',
        'collected_at' => now(),
        'collected_by' => $admin->id,
        'warehouse_id' => $destWh->id,
    ]);

    // 7. STATEMENT & ALLOCATION
    $statementService = app(CustomerStatementService::class);
    $summary = $statementService->getSummary($customer);
    expect($summary['total_charged_cents'])->toBe(3893);
    expect($summary['total_paid_cents'])->toBe(3800);
    expect($summary['outstanding_cents'])->toBe(93);

    // 8. PAYMENT REVERSAL
    $payment = Payment::where('customer_id', $customer->id)->first();
    $paymentService = app(PaymentService::class);
    $paymentService->reversePayment($payment, $admin, 'خطأ في الادخال');

    $summaryAfterReversal = $statementService->getSummary($customer);
    expect($summaryAfterReversal['outstanding_cents'])->toBe(3893);
});
