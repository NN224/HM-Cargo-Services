<?php

use App\Enums\PackageStatus;
use App\Models\AuditLog;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Schema;

test('the package journey has seven ordered normal steps', function () {
    expect(PackageStatus::journeySteps())->toBe([
        PackageStatus::ReceivedOrigin,
        PackageStatus::ArrivedOriginAirport,
        PackageStatus::InTransit,
        PackageStatus::ArrivedTransit,
        PackageStatus::DepartedTransit,
        PackageStatus::ArrivedDestination,
        PackageStatus::Collected,
    ])
        ->and(PackageStatus::ReceivedOrigin->journeyPosition())->toBe(0)
        ->and(PackageStatus::ArrivedOriginAirport->journeyPosition())->toBe(1)
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
