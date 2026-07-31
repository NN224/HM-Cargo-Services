<?php

use App\Enums\PackageStatus;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('the package journey has five ordered normal steps and legacy departure states are inactive', function () {
    expect(PackageStatus::journeySteps())->toBe([
        PackageStatus::ReceivedOrigin,
        PackageStatus::ArrivedOriginAirport,
        PackageStatus::ArrivedTransit,
        PackageStatus::ArrivedDestination,
        PackageStatus::Collected,
    ])
        ->and(PackageStatus::ReceivedOrigin->journeyPosition())->toBe(0)
        ->and(PackageStatus::ArrivedOriginAirport->journeyPosition())->toBe(1)
        ->and(PackageStatus::InTransit->journeyPosition())->toBeNull()
        ->and(PackageStatus::DepartedTransit->journeyPosition())->toBeNull()
        ->and(PackageStatus::Missing->journeyPosition())->toBeNull();
});

test('a route stores the three dynamic journey labels', function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $route = Route::create([
        'name' => 'Dubai to Syria',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $damascus->id,
        'origin_airport_name' => 'مطار دبي',
        'destination_airport_name' => 'مطار بيروت',
        'delivery_office_name' => 'مكتب دمشق',
    ]);

    expect($route->origin_airport_name)->toBe('مطار دبي')
        ->and($route->destination_airport_name)->toBe('مطار بيروت')
        ->and($route->delivery_office_name)->toBe('مكتب دمشق');
});

test('packages and package events store journey metadata', function () {
    expect(Schema::hasColumns('packages', [
        'is_delayed',
        'delay_reason',
        'delay_reason_is_public',
        'delayed_at',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('package_status_events', [
            'previous_status',
            'event_kind',
            'private_reason',
            'public_reason',
        ]))->toBeTrue();
});

test('the retirement migration normalizes current package states without deleting history', function () {
    $warehouse = Warehouse::create(['name' => 'مستودع الترحيل', 'location' => 'اختبار']);
    $user = User::factory()->create();
    $customer = Customer::create(['name' => 'عميل الترحيل', 'phone' => '+971500009991']);
    $shipment = Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'مستلم الترحيل',
        'recipient_phone' => '+96170009991',
    ]);
    $originDeparture = $shipment->packages()->create([
        'weight_kg' => 1,
        'status' => PackageStatus::InTransit,
    ]);
    $destinationDeparture = $shipment->packages()->create([
        'weight_kg' => 2,
        'status' => PackageStatus::DepartedTransit,
    ]);

    DB::table('package_status_events')->insert([
        'package_id' => $originDeparture->id,
        'warehouse_id' => $warehouse->id,
        'user_id' => $user->id,
        'status' => PackageStatus::InTransit->value,
        'event_kind' => 'progress',
        'scanned_at' => now(),
        'source' => 'migration_test',
    ]);

    $migration = require database_path('migrations/2026_07_30_161445_retire_package_departure_journey_statuses.php');
    $migration->up();

    expect($originDeparture->fresh()->status)->toBe(PackageStatus::ArrivedOriginAirport)
        ->and($destinationDeparture->fresh()->status)->toBe(PackageStatus::ArrivedTransit)
        ->and(DB::table('package_status_events')->where('package_id', $originDeparture->id)->value('status'))
        ->toBe(PackageStatus::InTransit->value);
});

test('audit logs are append only', function () {
    $user = User::factory()->create();

    $auditLog = AuditLog::create([
        'user_id' => $user->id,
        'action' => 'package_journey_corrected',
        'auditable_type' => Route::class,
        'auditable_id' => 1,
        'before' => ['status' => 'in_transit'],
        'after' => ['status' => 'arrived_transit'],
        'reason' => 'تصحيح اختبار',
        'created_at' => now(),
    ]);

    expect(fn () => $auditLog->update(['reason' => 'تعديل']))->toThrow(LogicException::class)
        ->and(fn () => $auditLog->delete())->toThrow(LogicException::class);
});
