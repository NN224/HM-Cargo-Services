<?php

use App\Enums\BatchStatus;
use App\Enums\PackageStatus;
use App\Enums\ShipmentStatus;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\Warehouse;

use function Pest\Laravel\getJson;

beforeEach(function () {
    $origin = Warehouse::create(['name' => 'مستودع دبي', 'location' => 'الإمارات']);
    $dest = Warehouse::create(['name' => 'مستودع بيروت', 'location' => 'لبنان']);

    $route = Route::create([
        'name' => 'دبي - لبنان',
        'origin_warehouse_id' => $origin->id,
        'destination_warehouse_id' => $dest->id,
        'transit_warehouse_id' => null,
        'base_cost_per_kg_cents' => 200,
    ]);

    $this->batch = Batch::create([
        'reference' => 'BATCH-API-TEST',
        'route_id' => $route->id,
        'status' => BatchStatus::InTransit,
        'cost_per_kg_cents' => 200,
    ]);

    $customer = Customer::create([
        'name' => 'عميل التتبع',
        'phone' => '+971500001111',
    ]);

    $this->shipment = new Shipment([
        'customer_id' => $customer->id,
        'reference' => 'HM-2026-000001',
        'public_token' => 'test-token-abcdef1234567890abcdef1234567890abcdef12',
        'recipient_name' => 'أحمد العلي',
        'recipient_phone' => '+96171000001',
        'status' => ShipmentStatus::InTransit,
    ]);
    $this->shipment->save();
    $this->shipment->forceFill([
        'batch_id' => $this->batch->id,
        'final_charge_cents' => 4500,
        'paid_amount_cents' => 0,
    ])->save();

    Package::create([
        'shipment_id' => $this->shipment->id,
        'barcode' => 'PKG-API-001',
        'weight_kg' => '5.0000',
        'status' => PackageStatus::ReceivedOrigin->value,
    ]);
});

// ---------------------------------------------------------------------------
// Token lookup
// ---------------------------------------------------------------------------

it('returns tracking data by token', function () {
    getJson('/api/track/test-token-abcdef1234567890abcdef1234567890abcdef12')
        ->assertOk()
        ->assertJsonStructure([
            'reference',
            'recipient',
            'route',
            'status',
            'status_label',
            'total_weight_kg',
            'package_count',
            'arrived_count',
            'progress_ratio',
            'delayed_count',
            'notices',
            'packages' => [[
                'barcode',
                'description',
                'weight_kg',
                'status',
                'status_label',
                'stage_label',
                'is_delayed',
                'is_exception',
                'delay_reason',
                'journey' => [['status', 'label', 'state', 'occurred_at']],
            ]],
            'journey' => [['status', 'label', 'completed_count', 'current_count', 'delayed_count']],
        ])
        ->assertJsonMissingPath('financial')
        ->assertJsonPath('reference', 'HM-2026-000001')
        ->assertJsonPath('status', ShipmentStatus::InTransit->value);
});

it('returns 404 for unknown token', function () {
    getJson('/api/track/completely-unknown-token-that-does-not-exist')
        ->assertNotFound()
        ->assertJsonPath('error', 'not_found');
});

// ---------------------------------------------------------------------------
// Reference lookup — removed on purpose
// ---------------------------------------------------------------------------

/*
 * References are sequential (HM-2026-000001, -000002, ...). While this
 * endpoint existed, anyone could walk the range and read a recipient name,
 * route and outstanding balance for every shipment in the system. It was
 * removed rather than throttled, because throttling only slows enumeration
 * down. The route is asserted gone, not the controller method, so that
 * re-adding it under any implementation fails here.
 */
it('no longer resolves a shipment by its readable reference', function (string $path) {
    getJson($path)->assertNotFound();
})->with([
    '/api/track/ref/HM-2026-000001',
    '/api/track/ref/hm-2026-000001',
]);

it('has no route registered for reference lookup', function () {
    $paths = collect(app('router')->getRoutes())->map(fn ($route): string => $route->uri());

    expect($paths)->not->toContain('api/track/ref/{reference}');
});

// ---------------------------------------------------------------------------
// Privacy — must not expose sensitive data
// ---------------------------------------------------------------------------

it('does not expose full recipient name', function () {
    $response = getJson('/api/track/test-token-abcdef1234567890abcdef1234567890abcdef12')
        ->assertOk();

    $recipient = $response->json('recipient');
    expect($recipient)->not->toBe('أحمد العلي');
    expect($recipient)->toContain('***');
});

it('does not expose profit or cost data', function () {
    $response = getJson('/api/track/test-token-abcdef1234567890abcdef1234567890abcdef12')
        ->assertOk();

    $json = $response->getContent();
    expect($json)->not->toContain('profit');
    expect($json)->not->toContain('cost_per_kg');
    expect($json)->not->toContain('rate_per_kg');
});

// ---------------------------------------------------------------------------
// CORS
// ---------------------------------------------------------------------------

it('returns CORS headers for hmcargoservices.com origin', function () {
    $this->withHeaders(['Origin' => 'https://hmcargoservices.com'])
        ->getJson('/api/track/test-token-abcdef1234567890abcdef1234567890abcdef12')
        ->assertOk()
        ->assertHeader('Access-Control-Allow-Origin', 'https://hmcargoservices.com');
});

it('does not return CORS headers for unknown origins', function () {
    $this->withHeaders(['Origin' => 'https://evil.com'])
        ->getJson('/api/track/test-token-abcdef1234567890abcdef1234567890abcdef12')
        ->assertOk()
        ->assertHeaderMissing('Access-Control-Allow-Origin');
});
