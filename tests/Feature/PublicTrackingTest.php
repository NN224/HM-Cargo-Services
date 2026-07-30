<?php

use App\Enums\BatchStatus;
use App\Enums\PackageStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->origin = Warehouse::create(['name' => 'دبي', 'location' => 'الإمارات']);
    $this->transit = Warehouse::create(['name' => 'بيروت', 'location' => 'لبنان']);
    $this->destination = Warehouse::create(['name' => 'دمشق', 'location' => 'سوريا']);

    $this->route = Route::create([
        'name' => 'دبي ← بيروت ← دمشق',
        'origin_warehouse_id' => $this->origin->id,
        'transit_warehouse_id' => $this->transit->id,
        'destination_warehouse_id' => $this->destination->id,
        'origin_airport_name' => 'مطار دبي',
        'destination_airport_name' => 'مطار بيروت',
        'delivery_office_name' => 'مكتب دمشق',
    ]);
    $this->batch = Batch::create([
        'reference' => 'BATCH-PRIVATE-REF',
        'route_id' => $this->route->id,
        'status' => BatchStatus::InTransit,
        'cost_per_kg_cents' => 887766,
    ]);
    $this->customer = Customer::create([
        'name' => 'عميل حسابه سري',
        'phone' => '+971500009999',
    ]);

    $this->shipment = new Shipment([
        'customer_id' => $this->customer->id,
        'reference' => 'HM-2026-TRACK',
        'public_token' => str_repeat('a', 48),
        'recipient_name' => 'أحمد محمد السالم',
        'recipient_phone' => '+963944445555',
        'status' => ShipmentStatus::PartialAtDestination,
    ]);
    $this->shipment->id = 987654321;
    $this->shipment->save();
    $this->shipment->forceFill([
        'batch_id' => $this->batch->id,
        'rate_per_kg_cents' => 776655,
        'computed_charge_cents' => 999999,
        'final_charge_cents' => 9250,
        'paid_amount_cents' => 4000,
    ])->save();

    $this->packages = collect([
        publicTrackingPackage($this->shipment, '0.1000', PackageStatus::ArrivedDestination),
        publicTrackingPackage($this->shipment, '0.2000', PackageStatus::Collected),
        publicTrackingPackage($this->shipment, '0.3000', PackageStatus::ArrivedTransit),
        publicTrackingPackage($this->shipment, '9.9999', PackageStatus::Cancelled),
    ]);

    $this->otherShipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'reference' => 'HM-OTHER-SECRET',
        'public_token' => str_repeat('b', 48),
        'recipient_name' => 'مستلم شحنة أخرى',
        'recipient_phone' => '+963911111111',
        'status' => ShipmentStatus::Pending,
    ]);

    $this->employee = User::create([
        'name' => 'موظف سري للغاية',
        'email' => 'private-tracker@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::Administrator,
    ]);

    DB::table('package_status_events')->insert([
        [
            'package_id' => $this->packages[2]->id,
            'status' => PackageStatus::Missing->value,
            'warehouse_id' => $this->transit->id,
            'user_id' => $this->employee->id,
            'scanned_at' => now()->subDay(),
            'source' => 'private-source-name',
            'note' => 'ملاحظة داخلية شديدة السرية',
        ],
        [
            'package_id' => $this->packages[0]->id,
            'status' => PackageStatus::ArrivedDestination->value,
            'warehouse_id' => $this->destination->id,
            'user_id' => $this->employee->id,
            'scanned_at' => now(),
            'source' => 'camera',
            'note' => 'تفاصيل داخلية أخرى',
        ],
    ]);

    $paymentId = DB::table('payments')->insertGetId([
        'customer_id' => $this->customer->id,
        'amount_cents' => 4000,
        'method' => 'cash',
        'collected_at' => now(),
        'collected_by' => $this->employee->id,
        'warehouse_id' => $this->destination->id,
        'reference' => 'PAY-PRIVATE-REF',
        'notes' => 'PAYMENT-PRIVATE-NOTE',
        'receipt_number' => 'RCPT-PRIVATE-REF',
        'type' => 'payment',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('payment_allocations')->insert([
        'payment_id' => $paymentId,
        'shipment_id' => $this->shipment->id,
        'amount_cents' => 4000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

function publicTrackingPackage(
    Shipment $shipment,
    string $weight,
    PackageStatus $status,
): Package {
    return $shipment->packages()->create([
        'weight_kg' => $weight,
        'status' => $status,
        'source_barcode' => 'SOURCE-BARCODE-PRIVATE-'.$weight,
        'description' => 'وصف طرد داخلي '.$weight,
    ]);
}

test('public tracking exposes only the safe shipment projection without login', function () {
    $response = $this->get(route('tracking.show', $this->shipment->public_token));

    $response->assertOk()
        ->assertSee('dir="rtl"', escape: false)
        ->assertSee('HM-2026-TRACK')
        ->assertSee('أحمد م*** ا***', escape: false)
        ->assertSee('دبي ← بيروت ← دمشق', escape: false)
        ->assertSee('وصل بعضها إلى الوجهة', escape: false)
        ->assertSee('وصل ٢ من ٣', escape: false)
        ->assertSee('0.6000 كغ', escape: false)
        ->assertSee('92.50 $', escape: false)
        ->assertSee('40.00 $', escape: false)
        ->assertSee('52.50 $', escape: false)
        ->assertSee('مدفوع جزئياً', escape: false)
        ->assertSee('يوجد طرد يحتاج متابعة', escape: false)
        ->assertSee('وصل طرد إلى مستودع دمشق', escape: false)
        ->assertViewMissing('shipment');

    expect(array_keys($response->viewData('tracking')))->toBe([
        'reference',
        'recipient',
        'route',
        'stage',
        'progress',
        'total_weight',
        'final_charge',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'timeline',
        'journey',
        'qr',
    ]);

    foreach ([
        '987654321',
        'أحمد محمد السالم',
        '+963944445555',
        'عميل حسابه سري',
        'HM-OTHER-SECRET',
        'BATCH-PRIVATE-REF',
        '776655',
        '887766',
        '999999',
        'موظف سري للغاية',
        'ملاحظة داخلية شديدة السرية',
        'تفاصيل داخلية أخرى',
        'private-source-name',
        'PAY-PRIVATE-REF',
        'PAYMENT-PRIVATE-NOTE',
        'RCPT-PRIVATE-REF',
        $this->packages[0]->barcode,
        $this->packages[0]->source_barcode,
        $this->packages[0]->description,
        'الربح',
        'تكلفة الرحلة',
        'سعر الكيلو',
        'كشف الحساب',
        'مرجع الدفعة',
    ] as $forbidden) {
        $response->assertDontSee($forbidden, escape: false);
    }

    $this->assertGuest();
});

test('a valid package barcode redirects to the parent secure token route', function () {
    $this->get(route('tracking.show', $this->packages[0]->barcode))
        ->assertRedirect(route('tracking.show', $this->shipment->public_token));
});

test('unknown and malformed tracking values return the exact same public response', function () {
    $unknown = $this->get(route('tracking.show', str_repeat('z', 48)));
    $malformed = $this->get(route('tracking.show', 'not-a-real-token'));

    expect($unknown->status())->toBe(404)
        ->and($malformed->status())->toBe(404)
        ->and($unknown->getContent())->toBe($malformed->getContent());
});

test('public tracking attempts are rate limited', function () {
    for ($attempt = 1; $attempt <= 20; $attempt++) {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.77'])
            ->get('/track/unknown-'.$attempt)
            ->assertNotFound();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.77'])
        ->get('/track/one-attempt-too-many')
        ->assertTooManyRequests();
});
