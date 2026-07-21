<?php

use App\Models\Batch;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Models\Package;
use App\Services\BatchAssignmentService;

beforeEach(function () {
    $this->origin = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->destinationSyria = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);
    $this->destinationLebanon = Warehouse::create(['name' => 'Beirut', 'location' => 'Lebanon']);
    
    $this->customer = Customer::create([
        'name' => 'John Doe',
        'phone' => '123456789',
        'warehouse_id' => $this->origin->id,
    ]);

    $this->routeSyria = Route::create([
        'name' => 'DXB-DAM',
        'origin_warehouse_id' => $this->origin->id,
        'destination_warehouse_id' => $this->destinationSyria->id,
    ]);

    $this->routeLebanon = Route::create([
        'name' => 'DXB-BEY',
        'origin_warehouse_id' => $this->origin->id,
        'destination_warehouse_id' => $this->destinationLebanon->id,
    ]);

    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->routeSyria->id,
        'rate_per_kg_cents' => 1000, // $10/kg
    ]);

    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->routeLebanon->id,
        'rate_per_kg_cents' => 1200, // $12/kg
    ]);

    $this->batchSyria = Batch::create([
        'route_id' => $this->routeSyria->id,
        'cost_per_kg_cents' => 500,
    ]);
});

it('rejects batch assignment if shipment destination differs from batch route destination', function () {
    $shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'Jane',
        'recipient_phone' => '0000',
        'destination_warehouse_id' => $this->destinationLebanon->id,
    ]);
    
    Package::create([
        'shipment_id' => $shipment->id,
        'weight_kg' => '10',
    ]);

    $service = new BatchAssignmentService();

    expect(fn() => $service->assign($shipment, $this->batchSyria))
        ->toThrow(
            DomainException::class, 
            "الشحنة متجهة إلى Beirut، بينما مسار الرحلة ينتهي في Damascus. لا يمكن إسنادها."
        );
});

it('accepts batch assignment if shipment destination matches batch route destination', function () {
    $shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'Jane',
        'recipient_phone' => '0000',
        'destination_warehouse_id' => $this->destinationSyria->id,
    ]);

    Package::create([
        'shipment_id' => $shipment->id,
        'weight_kg' => '10',
    ]);

    $service = new BatchAssignmentService();
    $service->assign($shipment, $this->batchSyria);

    expect($shipment->fresh()->batch_id)->toBe($this->batchSyria->id);
});

it('accepts batch assignment for legacy shipments with no destination', function () {
    $shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'Jane',
        'recipient_phone' => '0000',
        'destination_warehouse_id' => null, // Legacy shipment
    ]);

    Package::create([
        'shipment_id' => $shipment->id,
        'weight_kg' => '10',
    ]);

    $service = new BatchAssignmentService();
    $service->assign($shipment, $this->batchSyria);

    expect($shipment->fresh()->batch_id)->toBe($this->batchSyria->id);
});
