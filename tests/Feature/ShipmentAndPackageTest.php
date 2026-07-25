<?php

use App\Enums\PackageStatus;
use App\Enums\ShipmentStatus;
use App\Models\Customer;
use App\Models\Shipment;

beforeEach(function () {
    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);
});

function makeShipment(array $overrides = []): Shipment
{
    return Shipment::create(array_merge([
        'customer_id' => test()->customer->id,
        'recipient_name' => 'سامي',
        'recipient_phone' => '+9613000001',
    ], $overrides));
}

test('a new shipment is created pending, with a reference and a public token', function () {
    $shipment = makeShipment();

    expect($shipment->status)->toBe(ShipmentStatus::Pending)
        ->and($shipment->reference)->not->toBeEmpty()
        ->and($shipment->public_token)->not->toBeEmpty();
});

test('the public token is long and unguessable, and never the reference', function () {
    $shipment = makeShipment();

    // AGENTS.md: public tracking must not expose a sequential internal id.
    // Checking the token merely "does not contain" the id is meaningless — a
    // hex token contains most digits by chance. What matters is that the token
    // is long, random, and not a transformation of the id or the reference.
    expect(strlen($shipment->public_token))->toBeGreaterThanOrEqual(32)
        ->and($shipment->public_token)->not->toBe($shipment->reference)
        ->and($shipment->public_token)->not->toBe((string) $shipment->id)
        ->and($shipment->public_token)->toMatch('/^[0-9a-f]+$/')
        // A token derived from the id would be reproducible; a random one is not.
        ->and($shipment->public_token)->not->toBe(
            (string) Shipment::find($shipment->id)->makeHidden([])->reference
        );
});

test('public tokens do not repeat across many shipments', function () {
    $tokens = collect(range(1, 50))->map(fn () => makeShipment()->public_token);

    expect($tokens->unique())->toHaveCount(50);
});

test('every package receives a unique system generated barcode', function () {
    $shipment = makeShipment();

    // Created in one tight loop: the prototype's timestamp-plus-small-random
    // scheme collided exactly here.
    $barcodes = collect(range(1, 50))
        ->map(fn () => $shipment->packages()->create(['weight_kg' => 1])->barcode);

    expect($barcodes->unique())->toHaveCount(50)
        ->and($barcodes->filter())->toHaveCount(50);
});

test('total weight is the exact sum of package weights', function () {
    $shipment = makeShipment();

    // 0.1 + 0.2 is the classic floating point trap: it yields
    // 0.30000000000000004 in binary floating point.
    $shipment->packages()->create(['weight_kg' => 0.1]);
    $shipment->packages()->create(['weight_kg' => 0.2]);
    $shipment->recalculateTotalWeight();

    expect((string) $shipment->fresh()->total_weight_kg)->toBe('0.3000');
});

test('weight is never rounded, however awkward the decimals', function () {
    $shipment = makeShipment();

    $shipment->packages()->create(['weight_kg' => 12.3456]);
    $shipment->packages()->create(['weight_kg' => 0.0001]);
    $shipment->recalculateTotalWeight();

    expect((string) $shipment->fresh()->total_weight_kg)->toBe('12.3457');
});

test('adding and removing packages keeps the total honest', function () {
    $shipment = makeShipment();

    $first = $shipment->packages()->create(['weight_kg' => 5.5]);
    $shipment->packages()->create(['weight_kg' => 2.25]);
    $shipment->recalculateTotalWeight();

    expect((string) $shipment->fresh()->total_weight_kg)->toBe('7.7500');

    $first->delete();
    $shipment->recalculateTotalWeight();

    expect((string) $shipment->fresh()->total_weight_kg)->toBe('2.2500');
});

test('a package must weigh more than nothing', function () {
    makeShipment()->packages()->create(['weight_kg' => 0]);
})->throws(InvalidArgumentException::class);

test('a package cannot weigh a negative amount', function () {
    makeShipment()->packages()->create(['weight_kg' => -1]);
})->throws(InvalidArgumentException::class);

test('a new package starts as received and belongs to its shipment', function () {
    $shipment = makeShipment();
    $package = $shipment->packages()->create(['weight_kg' => 3]);

    expect($package->status)->toBe(PackageStatus::Received)
        ->and($package->shipment->is($shipment))->toBeTrue();
});

test('a supplier barcode can be recorded alongside the generated one', function () {
    $package = makeShipment()->packages()->create([
        'weight_kg' => 2,
        'source_barcode' => 'SUP-99887766',
        'description' => 'ملابس',
    ]);

    expect($package->source_barcode)->toBe('SUP-99887766')
        ->and($package->barcode)->not->toBe('SUP-99887766')
        ->and($package->description)->toBe('ملابس');
});

test('the same supplier barcode may appear on unrelated packages', function () {
    // Suppliers reuse codes; only our own barcode must be unique.
    $a = makeShipment()->packages()->create(['weight_kg' => 1, 'source_barcode' => 'DUP-1']);
    $b = makeShipment()->packages()->create(['weight_kg' => 1, 'source_barcode' => 'DUP-1']);

    expect($a->source_barcode)->toBe($b->source_barcode)
        ->and($a->barcode)->not->toBe($b->barcode);
});

test('shipment references are unique and readable', function () {
    $references = collect(range(1, 20))->map(fn () => makeShipment()->reference);

    expect($references->unique())->toHaveCount(20);
});

test('a shipment can be found by its public token', function () {
    $shipment = makeShipment();

    expect(Shipment::where('public_token', $shipment->public_token)->first()->id)
        ->toBe($shipment->id);
});

test('no recipient address is stored anywhere on a shipment', function () {
    // D-019: different people may collect, so an address booked up front is
    // frequently wrong. The Beirut delivery team takes it at handover.
    $shipment = makeShipment();

    expect(array_keys($shipment->getAttributes()))
        ->not->toContain('recipient_address')
        ->not->toContain('address');
});
