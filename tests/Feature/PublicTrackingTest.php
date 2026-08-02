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
        ->assertSee('وصل 2 من 3', escape: false)
        ->assertSee('0.6 كغ', escape: false)
        ->assertViewMissing('shipment');

    expect(array_keys($response->viewData('tracking')))->toBe([
        'reference',
        'recipient',
        'route',
        'status',
        'status_label',
        'total_weight_kg',
        'packages',
        'package_count',
        'arrived_count',
        'progress_ratio',
        'delayed_count',
        'notices',
        'journey',
    ]);

    /*
     * Published on purpose: the customer identifies a box by the barcode
     * printed on its label and by what is written on it. The supplier's own
     * barcode is a third party's data and stays out.
     */
    foreach ([
        $this->packages[0]->barcode,
        $this->packages[0]->description,
    ] as $published) {
        $response->assertSee($published, escape: false);
    }

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
        $this->packages[0]->source_barcode,
        // Charges left the public projection entirely — page and API alike.
        '92.50',
        '40.00',
        '52.50',
        'المتبقي',
        'المدفوع',
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

/*
 * Caught by looking at the rendered page, not by a test: a shipment whose
 * boxes were all sitting at the origin airport drew an empty progress bar,
 * because the bar was filled from the arrived count. Nothing had arrived, but
 * plenty had happened.
 */
test('progress reflects distance travelled even when nothing has arrived yet', function () {
    foreach ([$this->packages[0], $this->packages[1], $this->packages[2]] as $package) {
        $package->forceFill(['status' => PackageStatus::ArrivedOriginAirport->value])->save();
    }

    $tracking = $this->get(route('tracking.show', $this->shipment->public_token))
        ->assertOk()
        ->viewData('tracking');

    // Step 2 of 5 for every package.
    expect($tracking['arrived_count'])->toBe(0)
        ->and($tracking['progress_ratio'])->toBe(0.4);
});

/*
 * Collection is the last step and the end of the journey at once. Treated as
 * merely "current", the final dot stayed blue and the bar stopped short, so a
 * delivered shipment never read as finished.
 */
test('a fully delivered shipment reads as complete on every step', function () {
    foreach ([$this->packages[0], $this->packages[1], $this->packages[2]] as $package) {
        $package->forceFill(['status' => PackageStatus::Collected->value])->save();
    }

    $tracking = $this->get(route('tracking.show', $this->shipment->public_token))
        ->assertOk()
        ->viewData('tracking');

    $lastStep = end($tracking['journey']);

    expect($tracking['progress_ratio'])->toBe(1.0)
        ->and($lastStep['completed_count'])->toBe(3)
        ->and($lastStep['current_count'])->toBe(0);

    foreach ($tracking['packages'] as $package) {
        expect(array_column($package['journey'], 'state'))
            ->each->toBe('done');
    }
});

test('a cancelled package is left off the public page entirely', function () {
    $cancelled = $this->packages[3];

    $this->get(route('tracking.show', $this->shipment->public_token))
        ->assertOk()
        ->assertDontSee($cancelled->barcode, escape: false)
        ->assertSee($this->packages[0]->barcode, escape: false);
});

test('a package held as an exception says so without naming the internal note', function () {
    $this->packages[2]->forceFill(['status' => PackageStatus::Missing->value])->save();

    $this->get(route('tracking.show', $this->shipment->public_token))
        ->assertOk()
        ->assertSee('مفقود', escape: false)
        ->assertDontSee('ملاحظة داخلية شديدة السرية', escape: false);
});

/*
 * PackageJourneyService writes a delay to both the package row and an event,
 * putting the reason in public_reason or private_reason according to what the
 * administrator chose. Only the event decides what is published, so these two
 * tests drive it the way the service does rather than setting columns.
 */
test('a delay reason marked public reaches the page once, not once per package', function () {
    foreach ([$this->packages[0], $this->packages[2]] as $package) {
        $package->forceFill(['is_delayed' => true])->save();

        DB::table('package_status_events')->insert([
            'package_id' => $package->id,
            'status' => $package->status->value,
            'warehouse_id' => $this->transit->id,
            'user_id' => $this->employee->id,
            'scanned_at' => now(),
            'source' => 'journey_delay',
            'event_kind' => 'delay',
            'public_reason' => 'تأخير في التخليص الجمركي',
        ]);
    }

    $body = $this->get(route('tracking.show', $this->shipment->public_token))
        ->assertOk()
        ->assertSee('تأخير في التخليص الجمركي', escape: false)
        ->getContent();

    // Two packages, one cause: the banner states it once. The reason still
    // repeats inside each package's own row, which is the detail, not the
    // summary.
    expect(substr_count($body, 'class="notice"'))->toBe(1);
});

test('a delay reason kept private never reaches the page', function () {
    DB::table('package_status_events')->insert([
        'package_id' => $this->packages[2]->id,
        'status' => $this->packages[2]->status->value,
        'warehouse_id' => $this->transit->id,
        'user_id' => $this->employee->id,
        'scanned_at' => now(),
        'source' => 'journey_delay',
        'event_kind' => 'delay',
        'private_reason' => 'سبب داخلي لا يُنشر',
        'public_reason' => null,
    ]);

    $this->get(route('tracking.show', $this->shipment->public_token))
        ->assertOk()
        ->assertDontSee('سبب داخلي لا يُنشر', escape: false);
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
