<?php

use App\Enums\BatchStatus;
use App\Enums\PackageStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Filament\Pages\ScanPackages;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PackageScanService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->dubai = Warehouse::create(['name' => 'دبي', 'location' => 'الإمارات']);
    $this->beirut = Warehouse::create(['name' => 'بيروت', 'location' => 'لبنان']);
    $this->damascus = Warehouse::create(['name' => 'دمشق', 'location' => 'سوريا']);

    $this->route = Route::create([
        'name' => 'دبي ← بيروت ← دمشق',
        'origin_warehouse_id' => $this->dubai->id,
        'transit_warehouse_id' => $this->beirut->id,
        'destination_warehouse_id' => $this->damascus->id,
        'origin_airport_name' => 'مطار دبي',
        'destination_airport_name' => 'مطار بيروت',
        'delivery_office_name' => 'مكتب دمشق',
    ]);
    $this->batch = Batch::create([
        'route_id' => $this->route->id,
        'status' => BatchStatus::InTransit,
    ]);
    $this->customer = Customer::create(['name' => 'عميل المسح', 'phone' => '+971500000301']);
    $this->employee = User::create([
        'name' => 'موظف دمشق',
        'email' => 'scanner@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $this->damascus->id,
    ]);
});

function scanTestShipment(Batch $batch, Customer $customer, array $statuses = []): Shipment
{
    $shipment = Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'المستلم',
        'recipient_phone' => '+963900000301',
        'status' => ShipmentStatus::InTransit,
    ]);
    $shipment->forceFill(['batch_id' => $batch->id])->save();

    foreach ($statuses ?: [PackageStatus::DepartedTransit, PackageStatus::DepartedTransit] as $index => $status) {
        $shipment->packages()->create([
            'weight_kg' => $index === 0 ? '0.1000' : '0.2000',
            'status' => $status,
        ]);
    }

    return $shipment->fresh();
}

test('scanning one destination package advances only one ordered step and records the event', function () {
    $shipment = scanTestShipment($this->batch, $this->customer);
    $package = $shipment->packages->first();

    $scanned = app(PackageScanService::class)->scan(
        $package->barcode,
        $this->damascus,
        $this->employee,
        'camera',
        'وصول فعلي',
    );

    expect($scanned->status)->toBe(PackageStatus::ArrivedDestination)
        ->and($shipment->fresh()->status)->toBe(ShipmentStatus::PartialAtDestination)
        ->and($shipment->packages->last()->fresh()->status)->toBe(PackageStatus::DepartedTransit)
        ->and((string) $shipment->fresh()->total_weight_kg)->toBe('0.3000');

    $this->assertDatabaseHas('package_status_events', [
        'package_id' => $package->id,
        'previous_status' => PackageStatus::DepartedTransit->value,
        'status' => PackageStatus::ArrivedDestination->value,
        'event_kind' => 'progress',
        'warehouse_id' => $this->damascus->id,
        'user_id' => $this->employee->id,
        'source' => 'camera',
        'note' => 'وصول فعلي',
    ]);
});

test('a scanned tracking URL resolves to the same package', function () {
    $shipment = scanTestShipment($this->batch, $this->customer);
    $package = $shipment->packages->first();

    // The camera decodes the package QR and submits the tracking URL, not the
    // bare code. The scan must still land on this exact package.
    $scanned = app(PackageScanService::class)->scan(
        $package->trackingUrl(),
        $this->damascus,
        $this->employee,
        'camera_or_scanner',
    );

    expect($scanned->id)->toBe($package->id)
        ->and($scanned->status)->toBe(PackageStatus::ArrivedDestination);
});

test('the shipment becomes ready only after every active package reaches destination', function () {
    $shipment = scanTestShipment($this->batch, $this->customer);
    $service = app(PackageScanService::class);

    foreach ($shipment->packages as $package) {
        $service->scan($package->barcode, $this->damascus, $this->employee);
    }

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::ReadyForCollection);
});

test('a cancelled package is excluded from the destination aggregate', function () {
    $shipment = scanTestShipment($this->batch, $this->customer, [
        PackageStatus::DepartedTransit,
        PackageStatus::Cancelled,
    ]);

    app(PackageScanService::class)->scan(
        $shipment->packages()->where('status', PackageStatus::DepartedTransit->value)->firstOrFail()->barcode,
        $this->damascus,
        $this->employee,
    );

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::ReadyForCollection);
});

test('a transit scan records transit arrival without inventing destination arrival', function () {
    $beirutEmployee = User::create([
        'name' => 'موظف بيروت',
        'email' => 'beirut-scanner@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $this->beirut->id,
    ]);
    $shipment = scanTestShipment($this->batch, $this->customer, [
        PackageStatus::InTransit,
        PackageStatus::InTransit,
    ]);

    app(PackageScanService::class)->scan(
        $shipment->packages->first()->barcode,
        $this->beirut,
        $beirutEmployee,
    );

    expect($shipment->packages->first()->fresh()->status)->toBe(PackageStatus::ArrivedTransit)
        ->and($shipment->fresh()->status)->toBe(ShipmentStatus::PartialAtTransit);
});

test('a direct-route destination scan cannot record the same physical arrival twice', function () {
    $directRoute = Route::create([
        'name' => 'دبي ← دمشق مباشر',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
        'origin_airport_name' => 'مطار دبي',
        'destination_airport_name' => 'مطار دمشق',
        'delivery_office_name' => 'مكتب دمشق',
    ]);
    $directBatch = Batch::create([
        'route_id' => $directRoute->id,
        'status' => BatchStatus::InTransit,
    ]);
    $shipment = scanTestShipment($directBatch, $this->customer, [PackageStatus::InTransit]);
    $package = $shipment->packages->first();
    $service = app(PackageScanService::class);

    $service->scan($package->barcode, $this->damascus, $this->employee);

    expect($package->fresh()->status)->toBe(PackageStatus::ArrivedTransit);

    expect(fn () => $service->scan(
        $package->barcode,
        $this->damascus,
        $this->employee,
    ))->not->toThrow(DomainException::class);

    expect(DB::table('package_status_events')->where('package_id', $package->id)->count())
        ->toBe(2)
        ->and($package->fresh()->status)->toBe(PackageStatus::DepartedTransit);
});

test('a direct-route destination scan cannot skip origin airport stages', function () {
    $directRoute = Route::create([
        'name' => 'دبي ← دمشق مباشر ٢',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
        'origin_airport_name' => 'مطار دبي',
        'destination_airport_name' => 'مطار دمشق',
        'delivery_office_name' => 'مكتب دمشق',
    ]);
    $directBatch = Batch::create([
        'route_id' => $directRoute->id,
        'status' => BatchStatus::InTransit,
    ]);
    $shipment = scanTestShipment($directBatch, $this->customer, [PackageStatus::ReceivedOrigin]);

    expect(fn () => app(PackageScanService::class)->scan(
        $shipment->packages->first()->barcode,
        $this->damascus,
        $this->employee,
    ))->toThrow(DomainException::class, 'غير مخوّل');
});

test('warehouse policy refuses a scan outside the employee warehouse', function () {
    $shipment = scanTestShipment($this->batch, $this->customer);
    $package = $shipment->packages->first();

    expect(fn () => app(PackageScanService::class)->scan(
        $package->barcode,
        $this->beirut,
        $this->employee,
    ))->toThrow(AuthorizationException::class);

    expect($package->fresh()->status)->toBe(PackageStatus::DepartedTransit)
        ->and(DB::table('package_status_events')->count())->toBe(0);
});

test('the mobile scanning page submits a barcode through Livewire', function () {
    $shipment = scanTestShipment($this->batch, $this->customer);
    $shipment->update(['recipient_name' => 'المستلم التجريبي']);
    $package = $shipment->packages->first();
    $this->actingAs($this->employee);

    Livewire::test(ScanPackages::class)
        ->assertSee('مسح وصول طرد', escape: false)
        ->assertSee('الباركود', escape: false)
        ->fillForm([
            'barcode' => $package->barcode,
            'warehouse_id' => $this->damascus->id,
        ])
        ->call('scan')
        ->assertHasNoFormErrors()
        ->assertSee($shipment->reference)
        ->assertSee('المستلم', escape: false)
        ->assertSee('المستلم التجريبي', escape: false)
        ->assertSee('1 من 2', escape: false)
        ->assertSee('0.3000 كغ', escape: false)
        ->assertSee('المطلوب: 0.00 $، المدفوع: 0.00 $، المتبقي: 0.00 $', escape: false);

    expect($package->fresh()->status)->toBe(PackageStatus::ArrivedDestination);
});

test('the scanning page shows an Arabic error for an unknown barcode', function () {
    $this->actingAs($this->employee);

    Livewire::test(ScanPackages::class)
        ->fillForm([
            'barcode' => 'PKG-NOT-FOUND',
            'warehouse_id' => $this->damascus->id,
        ])
        ->call('scan')
        ->assertHasFormErrors(['barcode'])
        ->assertSee('لم يُعثر على طرد بهذا الباركود', escape: false);
});

test('a damaged package cannot be cleared by an ordinary arrival scan', function () {
    $this->batch->forceFill(['status' => BatchStatus::InTransit])->save();
    $shipment = scanTestShipment($this->batch, $this->customer, [PackageStatus::Damaged]);
    $shipment->forceFill(['status' => ShipmentStatus::Exception])->save();

    expect(fn () => app(PackageScanService::class)->scan(
        $shipment->packages->first()->barcode,
        $this->damascus,
        $this->employee,
    ))->toThrow(DomainException::class, 'استثناء');

    expect($shipment->packages->first()->fresh()->status)->toBe(PackageStatus::Damaged)
        ->and($shipment->fresh()->status)->toBe(ShipmentStatus::Exception);
});

test('a physical arrival scan does not silently resolve a missing package', function () {
    $shipment = scanTestShipment($this->batch, $this->customer, [PackageStatus::Missing]);
    $shipment->forceFill(['status' => ShipmentStatus::Exception])->save();

    expect(fn () => app(PackageScanService::class)->scan(
        $shipment->packages->first()->barcode,
        $this->damascus,
        $this->employee,
    ))->toThrow(DomainException::class);

    expect($shipment->packages->first()->fresh()->status)->toBe(PackageStatus::Missing)
        ->and($shipment->fresh()->status)->toBe(ShipmentStatus::Exception);
});

test('an open undispatched batch cannot manufacture package arrival', function () {
    $this->batch->forceFill(['status' => BatchStatus::Open])->save();
    $shipment = scanTestShipment($this->batch, $this->customer, [PackageStatus::InTransit]);

    app(PackageScanService::class)->scan(
        $shipment->packages->first()->barcode,
        $this->damascus,
        $this->employee,
    );
})->throws(DomainException::class, 'لم تُرسل');

test('a transit route cannot reach destination before the package departs transit', function () {
    $this->batch->forceFill(['status' => BatchStatus::InTransit])->save();
    $shipment = scanTestShipment($this->batch, $this->customer, [PackageStatus::InTransit]);

    app(PackageScanService::class)->scan(
        $shipment->packages->first()->barcode,
        $this->damascus,
        $this->employee,
    );
})->throws(DomainException::class, 'العبور');

test('recalculation never regresses a collected shipment to ready', function () {
    $shipment = scanTestShipment($this->batch, $this->customer, [
        PackageStatus::Collected,
        PackageStatus::Collected,
    ]);
    $shipment->forceFill(['status' => ShipmentStatus::Collected])->save();

    $shipment->recalculateOperationalStatus();

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::Collected);
});

test('a package status event cannot be erased by deleting its package', function () {
    $this->batch->forceFill(['status' => BatchStatus::InTransit])->save();
    $shipment = scanTestShipment($this->batch, $this->customer, [PackageStatus::DepartedTransit]);
    $package = $shipment->packages->first();

    app(PackageScanService::class)->scan(
        $package->barcode,
        $this->damascus,
        $this->employee,
    );

    // Check the events exist BEFORE attempting the delete. On PostgreSQL a
    // failed statement aborts the surrounding transaction, so a query run
    // after the delete throws would fail with "transaction aborted" rather
    // than answer — the assertion must come first, and the throwing delete
    // last, so nothing queries the poisoned transaction.
    expect(DB::table('package_status_events')->where('package_id', $package->id)->exists())->toBeTrue()
        ->and(fn () => $package->delete())->toThrow(QueryException::class);
});
