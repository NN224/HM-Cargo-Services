<?php

use App\Enums\BatchStatus;
use App\Enums\Capability;
use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\BatchIntakeService;

beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->route = Route::create([
        'name' => 'Dubai → Syria',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $damascus->id,
    ]);

    $this->batch = Batch::create([
        'reference' => 'BCH-2026-0001',
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
    ]);

    $this->stranger = Customer::create(['name' => 'غريب', 'phone' => '+971500000099']);

    $this->service = app(BatchIntakeService::class);

    // A warehouse employee with no capabilities at all. Used wherever a call
    // does not need to record a rate, and as the base for the "without
    // ManageCustomers" scenario below.
    $this->clerk = User::create([
        'name' => 'موظف', 'email' => 'clerk@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $dubai->id,
    ]);

    // A warehouse employee explicitly granted manage_customers, distinct from
    // an administrator so the capability list itself is exercised rather than
    // the administrator short-circuit in hasCapability().
    $this->customersManager = User::create([
        'name' => 'مسؤول العملاء', 'email' => 'manager@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $dubai->id,
    ]);
    $this->customersManager->grantCapability(Capability::ManageCustomers);
});

test('an agreed rate supplied at intake becomes the customer rate and prices the shipment', function () {
    $shipment = $this->service->receive($this->batch, [
        'customer_id' => $this->stranger->id,
        'recipient_is_customer' => true,
        'agreed_rate_per_kg_cents' => 275,
        'packages' => [['weight_kg' => 2.0, 'description' => null]],
    ], $this->customersManager);

    expect($shipment->rate_per_kg_cents)->toBe(275)
        ->and($shipment->final_charge_cents)->toBe(550);

    expect(CustomerRate::where('customer_id', $this->stranger->id)
        ->where('route_id', $this->route->id)
        ->value('rate_per_kg_cents'))->toBe(275);
});

test('an agreed rate never overwrites a rate that already exists', function () {
    CustomerRate::create([
        'customer_id' => $this->stranger->id,
        'route_id' => $this->route->id,
        'rate_per_kg_cents' => 300,
    ]);

    $shipment = $this->service->receive($this->batch, [
        'customer_id' => $this->stranger->id,
        'recipient_is_customer' => true,
        // Ignored. Changing an agreed rate is the rates screen's job, behind
        // its confirmation — not a side effect of receiving boxes. Note the
        // clerk here has no ManageCustomers: the value is discarded before
        // the capability guard is ever reached, so this must still succeed.
        'agreed_rate_per_kg_cents' => 999,
        'packages' => [['weight_kg' => 1.0, 'description' => null]],
    ], $this->clerk);

    expect($shipment->rate_per_kg_cents)->toBe(300);

    expect(CustomerRate::where('customer_id', $this->stranger->id)
        ->where('route_id', $this->route->id)
        ->value('rate_per_kg_cents'))->toBe(300);
});

test('a zero or negative agreed rate is refused and nothing is written', function () {
    expect(fn () => $this->service->receive($this->batch, [
        'customer_id' => $this->stranger->id,
        'recipient_is_customer' => true,
        'agreed_rate_per_kg_cents' => 0,
        'packages' => [['weight_kg' => 1.0, 'description' => null]],
    ], $this->customersManager))->toThrow(DomainException::class);

    expect(Shipment::count())->toBe(0)
        ->and(CustomerRate::count())->toBe(0);
});

test('a warehouse employee without ManageCustomers cannot record an agreed rate through intake', function () {
    expect($this->clerk->hasCapability(Capability::ManageCustomers))->toBeFalse();

    // A crafted request supplying agreed_rate_per_kg_cents directly to the
    // service — bypassing the Filament form entirely — must still be
    // refused. Hiding the field on the form is not authorization (AGENTS.md).
    expect(fn () => $this->service->receive($this->batch, [
        'customer_id' => $this->stranger->id,
        'recipient_is_customer' => true,
        'agreed_rate_per_kg_cents' => 275,
        'packages' => [['weight_kg' => 2.0, 'description' => null]],
    ], $this->clerk))->toThrow(DomainException::class, 'إدارة العملاء');

    // The whole intake is one transaction: the refusal must unwind
    // everything, leaving no rate and no half-received shipment behind.
    expect(CustomerRate::count())->toBe(0)
        ->and(Shipment::count())->toBe(0);
});

test('a warehouse employee granted ManageCustomers can still record an agreed rate through intake', function () {
    $shipment = $this->service->receive($this->batch, [
        'customer_id' => $this->stranger->id,
        'recipient_is_customer' => true,
        'agreed_rate_per_kg_cents' => 275,
        'packages' => [['weight_kg' => 2.0, 'description' => null]],
    ], $this->customersManager);

    expect($shipment->rate_per_kg_cents)->toBe(275)
        ->and($shipment->final_charge_cents)->toBe(550);

    expect(CustomerRate::where('customer_id', $this->stranger->id)
        ->where('route_id', $this->route->id)
        ->value('rate_per_kg_cents'))->toBe(275);
});
