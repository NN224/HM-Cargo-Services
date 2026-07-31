<?php

use App\Enums\BatchStatus;
use App\Enums\PackageStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

test('public tracking shows five safe journey steps without retired departure stages', function () {
    $origin = Warehouse::create(['name' => 'دبي', 'location' => 'الإمارات']);
    $destination = Warehouse::create(['name' => 'دمشق', 'location' => 'سوريا']);
    $route = Route::create([
        'name' => 'دبي ← دمشق مباشر',
        'origin_warehouse_id' => $origin->id,
        'destination_warehouse_id' => $destination->id,
        'origin_airport_name' => 'مطار دبي',
        'destination_airport_name' => 'مطار دمشق',
        'delivery_office_name' => 'مكتب دمشق',
    ]);
    $batch = Batch::create(['route_id' => $route->id, 'status' => BatchStatus::InTransit]);
    $customer = Customer::create(['name' => 'عميل عام', 'phone' => '+971500000941']);
    $shipment = Shipment::create([
        'customer_id' => $customer->id,
        'reference' => 'HM-PUBLIC-JOURNEY',
        'public_token' => str_repeat('c', 48),
        'recipient_name' => 'سامي سالم',
        'recipient_phone' => '+963900000941',
        'status' => ShipmentStatus::InTransit,
    ]);
    $shipment->forceFill(['batch_id' => $batch->id, 'final_charge_cents' => 10000])->save();

    $first = $shipment->packages()->create([
        'weight_kg' => 1,
        'status' => PackageStatus::ArrivedOriginAirport,
    ]);
    $second = $shipment->packages()->create([
        'weight_kg' => 2,
        'status' => PackageStatus::ArrivedTransit,
        'is_delayed' => true,
        'delay_reason' => 'PRIVATE-DELAY-REASON',
        'delay_reason_is_public' => true,
        'delayed_at' => now(),
    ]);

    $actor = User::create([
        'name' => 'موظف داخلي',
        'email' => 'public-journey@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::Administrator,
    ]);

    DB::table('package_status_events')->insert([
        [
            'package_id' => $second->id,
            'previous_status' => PackageStatus::InTransit->value,
            'status' => PackageStatus::InTransit->value,
            'event_kind' => 'delay',
            'warehouse_id' => $origin->id,
            'user_id' => $actor->id,
            'scanned_at' => now(),
            'source' => 'journey_delay',
            'note' => null,
            'private_reason' => null,
            'public_reason' => 'تأخر التحميل للرحلة القادمة',
        ],
        [
            'package_id' => $first->id,
            'previous_status' => PackageStatus::ArrivedOriginAirport->value,
            'status' => PackageStatus::ReceivedOrigin->value,
            'event_kind' => 'correction',
            'warehouse_id' => $origin->id,
            'user_id' => $actor->id,
            'scanned_at' => now(),
            'source' => 'journey_correction',
            'note' => null,
            'private_reason' => 'PRIVATE-CORRECTION-REASON',
            'public_reason' => null,
        ],
    ]);

    $response = $this->get(route('tracking.show', $shipment->public_token));

    $response->assertOk()
        ->assertSee('وصل مستودع دبي', escape: false)
        ->assertSee('وصل مطار دبي', escape: false)
        ->assertSee('وصل مطار دمشق', escape: false)
        ->assertSee('وصل مكتب دمشق', escape: false)
        ->assertSee('استلمه العميل', escape: false)
        ->assertDontSee('غادر مطار دبي', escape: false)
        ->assertDontSee('غادر مطار دمشق', escape: false)
        ->assertDontSee('طرد في الطريق', escape: false)
        ->assertSee('تأخر التحميل للرحلة القادمة', escape: false)
        ->assertSee('١ حالياً', escape: false)
        ->assertSee('١ متأخر', escape: false);

    foreach ([
        'user_id',
        'employee',
        'private_reason',
        'PRIVATE-DELAY-REASON',
        'PRIVATE-CORRECTION-REASON',
        'internal',
        'batch cost',
        'profit',
        'collector',
        'موظف داخلي',
    ] as $forbidden) {
        $response->assertDontSee($forbidden, escape: false);
    }

    expect($response->viewData('tracking')['journey']['package_count'])->toBe(2)
        ->and($response->viewData('tracking')['journey']['delayed_count'])->toBe(1)
        ->and($response->viewData('tracking')['journey']['published_events'])->toHaveCount(1)
        ->and($response->viewData('tracking')['timeline'])->toHaveCount(1);
});
