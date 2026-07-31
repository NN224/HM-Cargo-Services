<?php

use App\Enums\BatchStatus;
use App\Enums\PackageStatus;
use App\Enums\ShipmentStatus;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Services\PackageJourneyProjection;

function journeyProjectionShipment(Route $route, array $statuses): Shipment
{
    $batch = Batch::create(['route_id' => $route->id, 'status' => BatchStatus::InTransit]);
    $customer = Customer::create(['name' => 'عميل العرض', 'phone' => '+971500000921']);
    $shipment = Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'المستلم',
        'recipient_phone' => '+963900000921',
        'status' => ShipmentStatus::InTransit,
    ]);
    $shipment->forceFill(['batch_id' => $batch->id])->save();

    foreach ($statuses as $index => $status) {
        $shipment->packages()->create([
            'weight_kg' => $index + 1,
            'status' => $status,
        ]);
    }

    return $shipment->fresh();
}

test('the package journey projection uses dynamic Arabic route labels', function () {
    $dubai = Warehouse::create(['name' => 'دبي', 'location' => 'الإمارات']);
    $beirut = Warehouse::create(['name' => 'دمشق', 'location' => 'سوريا']);
    $route = Route::create([
        'name' => 'دبي ← بيروت ← دمشق',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $beirut->id,
        'origin_airport_name' => 'مطار دبي',
        'destination_airport_name' => 'مطار بيروت',
        'delivery_office_name' => 'مكتب دمشق',
    ]);
    $shipment = journeyProjectionShipment($route, [PackageStatus::ArrivedOriginAirport]);

    $projection = app(PackageJourneyProjection::class)->forShipment($shipment);

    expect(array_column($projection['steps'], 'label'))->toBe([
        'وصلت مستودع دبي',
        'وصلت مطار دبي',
        'وصلت مطار بيروت',
        'وصلت مكتب دمشق',
        'استلمها العميل',
    ]);
});

test('the package journey projection counts split and delayed packages', function () {
    $dubai = Warehouse::create(['name' => 'دبي', 'location' => 'الإمارات']);
    $damascus = Warehouse::create(['name' => 'دمشق', 'location' => 'سوريا']);
    $route = Route::create([
        'name' => 'دبي ← دمشق',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $damascus->id,
        'origin_airport_name' => 'مطار دبي',
        'destination_airport_name' => 'مطار دمشق',
        'delivery_office_name' => 'مكتب دمشق',
    ]);
    $shipment = journeyProjectionShipment($route, [
        PackageStatus::ArrivedOriginAirport,
        PackageStatus::ArrivedTransit,
        PackageStatus::ArrivedTransit,
    ]);
    $shipment->packages()->latest('id')->firstOrFail()->forceFill([
        'is_delayed' => true,
        'delay_reason' => 'انتظار التحميل',
        'delay_reason_is_public' => true,
        'delayed_at' => now(),
    ])->save();

    $projection = app(PackageJourneyProjection::class)->forShipment($shipment);

    expect($projection['package_count'])->toBe(3)
        ->and($projection['delayed_count'])->toBe(1)
        ->and($projection['steps'][1]['current_count'])->toBe(1)
        ->and($projection['steps'][1]['completed_count'])->toBe(2)
        ->and($projection['steps'][2]['current_count'])->toBe(2)
        ->and($projection['steps'][2]['delayed_count'])->toBe(1);
});

test('read only projections use a safe fallback for incomplete route labels', function () {
    $dubai = Warehouse::create(['name' => 'دبي', 'location' => 'الإمارات']);
    $damascus = Warehouse::create(['name' => 'دمشق', 'location' => 'سوريا']);
    $route = Route::create([
        'name' => 'دبي ← دمشق غير مكتمل',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $damascus->id,
    ]);
    $shipment = journeyProjectionShipment($route, [PackageStatus::ReceivedOrigin]);

    $projection = app(PackageJourneyProjection::class)->forShipment($shipment);

    expect($projection['steps'][1]['label'])->toBe('وصلت غير مضبوط')
        ->and($projection['steps'][3]['label'])->toBe('وصلت غير مضبوط');
});
