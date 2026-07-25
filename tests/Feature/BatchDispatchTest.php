<?php

use App\Enums\BatchStatus;
use App\Enums\Capability;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Batches\BatchResource;
use App\Filament\Resources\Batches\Pages\CreateBatch;
use App\Filament\Resources\Batches\Pages\EditBatch;
use App\Filament\Resources\Batches\Pages\ListBatches;
use App\Filament\Resources\Batches\Pages\ViewBatch;
use App\Filament\Widgets\BatchProfitabilityWidget;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\BatchDispatchService;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->dubai = Warehouse::create(['name' => 'دبي', 'location' => 'الإمارات']);
    $this->damascus = Warehouse::create(['name' => 'دمشق', 'location' => 'سوريا']);

    $this->route = Route::create([
        'name' => 'دبي ← دمشق',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);

    $this->admin = User::create([
        'name' => 'مدير',
        'email' => 'batch-admin@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::Administrator,
        'warehouse_id' => $this->dubai->id,
    ]);

    $this->customer = Customer::create([
        'name' => 'عميل الرحلة',
        'phone' => '+971500000099',
    ]);

    $this->actingAs($this->admin);
});

function phaseFourBatch(Route $route): Batch
{
    return Batch::create(['route_id' => $route->id]);
}

function phaseFourShipment(Batch $batch, Customer $customer, string $weight, int $charge, ShipmentStatus $status = ShipmentStatus::Pending): Shipment
{
    $shipment = Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'المستلم',
        'recipient_phone' => '+963900000001',
        'status' => $status,
    ]);

    // These figures are derived or snapshotted and deliberately guarded from
    // mass assignment, so the test uses the same explicit path production
    // services must use.
    $shipment->forceFill([
        'batch_id' => $batch->id,
        'total_weight_kg' => $weight,
        'computed_charge_cents' => $charge,
        'final_charge_cents' => $charge,
    ])->save();

    return $shipment;
}

test('dispatch snapshots the cost and status atomically', function () {
    Carbon::setTestNow('2026-07-21 10:00:00');
    $batch = phaseFourBatch($this->route);

    $dispatched = app(BatchDispatchService::class)->dispatch($batch, 287);

    expect($dispatched->status)->toBe(BatchStatus::Dispatched)
        ->and($dispatched->cost_per_kg_cents)->toBe(287)
        ->and($dispatched->dispatched_on->toDateString())->toBe('2026-07-21')
        ->and(BatchResource::canEdit($dispatched))->toBeFalse();
});

test('an explicit zero cost is available and differs from a missing cost', function () {
    $batch = phaseFourBatch($this->route);

    app(BatchDispatchService::class)->dispatch($batch, 0);

    expect($batch->fresh()->cost_per_kg_cents)->toBe(0);

    Livewire::test(BatchProfitabilityWidget::class, ['record' => $batch->fresh()])
        ->assertSee('$0.00', escape: false)
        ->assertDontSee('غير متاحة', escape: false);
});

test('a dispatched batch cannot be dispatched again or have its snapshot replaced', function () {
    $batch = phaseFourBatch($this->route);
    $service = app(BatchDispatchService::class);

    $service->dispatch($batch, 287);

    expect(fn () => $service->dispatch($batch, 999))
        ->toThrow(DomainException::class, 'أُرسلت');

    expect($batch->fresh()->cost_per_kg_cents)->toBe(287)
        ->and($batch->fresh()->status)->toBe(BatchStatus::Dispatched);
});

test('the ordinary edit page is forbidden after dispatch', function () {
    $batch = phaseFourBatch($this->route);

    app(BatchDispatchService::class)->dispatch($batch, 287);

    $this->get(BatchResource::getUrl('edit', ['record' => $batch]))
        ->assertForbidden();
});

test('the edit button is hidden once a batch is dispatched', function () {
    // An open batch may still be edited, so the button is offered.
    $open = phaseFourBatch($this->route);

    Livewire::test(ViewBatch::class, ['record' => $open->getRouteKey()])
        ->assertActionVisible('edit');

    // Dispatch locks the batch (D-016); a button that only 403s reads as
    // broken, so it disappears rather than dead-ending.
    $dispatched = phaseFourBatch($this->route);
    app(BatchDispatchService::class)->dispatch($dispatched, 287);
    $dispatched->refresh();

    Livewire::test(ViewBatch::class, ['record' => $dispatched->getRouteKey()])
        ->assertActionHidden('edit');
});

test('the view action asks for cost and dispatches through the service', function () {
    $batch = phaseFourBatch($this->route);

    Livewire::test(ViewBatch::class, ['record' => $batch->getRouteKey()])
        ->assertSee('إرسال الرحلة', escape: false)
        ->callAction('dispatch', ['cost_per_kg_cents' => 415])
        ->assertHasNoActionErrors()
        ->assertRedirect(BatchResource::getUrl('view', ['record' => $batch]));

    expect($batch->fresh()->cost_per_kg_cents)->toBe(415)
        ->and($batch->fresh()->status)->toBe(BatchStatus::Dispatched);
});

test('the batches list is rendered in Arabic through Livewire', function () {
    $batch = phaseFourBatch($this->route);

    Livewire::test(ListBatches::class)
        ->assertSee('رقم الرحلة', escape: false)
        ->assertSee('المسار', escape: false)
        ->assertSee('الوزن', escape: false)
        ->assertSee('الحالة', escape: false)
        ->assertSee($batch->reference);
});

test('profitability uses active shipment revenue and exact SQL weight', function () {
    $batch = phaseFourBatch($this->route);
    $batch->forceFill(['cost_per_kg_cents' => 333])->save();

    phaseFourShipment($batch, $this->customer, '0.1000', 700);
    phaseFourShipment($batch, $this->customer, '0.2000', 1100);
    phaseFourShipment($batch, $this->customer, '50.0000', 9000, ShipmentStatus::Cancelled);

    Livewire::test(BatchProfitabilityWidget::class, ['record' => $batch])
        ->assertSee('الإيراد', escape: false)
        ->assertSee('$18.00', escape: false)
        ->assertSee('التكلفة', escape: false)
        ->assertSee('$1.00', escape: false)
        ->assertSee('الربح', escape: false)
        ->assertSee('$17.00', escape: false);
});

test('profit is unavailable while the batch cost is missing', function () {
    $batch = phaseFourBatch($this->route);
    phaseFourShipment($batch, $this->customer, '2.0000', 1200);

    Livewire::test(BatchProfitabilityWidget::class, ['record' => $batch])
        ->assertSee('$12.00', escape: false)
        ->assertSee('غير متاحة', escape: false)
        ->assertDontSee('$0.00', escape: false);
});

test('the discovered dashboard widget keeps each batch cost snapshot separate', function () {
    $first = phaseFourBatch($this->route);
    $first->forceFill(['cost_per_kg_cents' => 300])->save();
    phaseFourShipment($first, $this->customer, '1.0000', 900);

    $second = phaseFourBatch($this->route);
    $second->forceFill(['cost_per_kg_cents' => 500])->save();
    phaseFourShipment($second, $this->customer, '2.0000', 1600);

    Livewire::test(BatchProfitabilityWidget::class)
        ->assertSee('$25.00', escape: false)
        ->assertSee('$13.00', escape: false)
        ->assertSee('$12.00', escape: false);
});

test('an employee cannot dispatch without the pricing capability', function () {
    $employee = User::create([
        'name' => 'موظف',
        'email' => 'batch-clerk@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $this->dubai->id,
    ]);
    $this->actingAs($employee);

    expect(fn () => app(BatchDispatchService::class)
        ->dispatch(phaseFourBatch($this->route), 250))
        ->toThrow(DomainException::class, 'صلاحية');

    expect(BatchProfitabilityWidget::canView())->toBeFalse();
});

test('pricing capability never widens an employee beyond their warehouse', function () {
    $employee = User::create([
        'name' => 'مسعّر',
        'email' => 'batch-pricer@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $this->dubai->id,
        'capabilities' => [Capability::PriceShipments->value],
    ]);

    $aleppo = Warehouse::create(['name' => 'حلب', 'location' => 'سوريا']);
    $homs = Warehouse::create(['name' => 'حمص', 'location' => 'سوريا']);
    $unrelatedRoute = Route::create([
        'name' => 'حلب ← حمص',
        'origin_warehouse_id' => $aleppo->id,
        'destination_warehouse_id' => $homs->id,
    ]);
    Route::create([
        'name' => 'حمص ← دبي',
        'origin_warehouse_id' => $homs->id,
        'destination_warehouse_id' => $this->dubai->id,
    ]);
    $visible = phaseFourBatch($this->route);
    $hidden = phaseFourBatch($unrelatedRoute);

    $this->actingAs($employee);

    expect(BatchResource::getEloquentQuery()->pluck('id')->all())
        ->toBe([$visible->id])
        ->and(BatchProfitabilityWidget::canView())->toBeTrue()
        ->and(fn () => app(BatchDispatchService::class)->dispatch($hidden, 250))
        ->toThrow(DomainException::class, 'المستودع');

    $visible->forceFill(['cost_per_kg_cents' => 100])->save();
    $hidden->forceFill(['cost_per_kg_cents' => 100])->save();
    phaseFourShipment($visible, $this->customer, '1.0000', 500);
    phaseFourShipment($hidden, $this->customer, '1.0000', 900);

    Livewire::test(BatchProfitabilityWidget::class)
        ->assertSee('$5.00', escape: false)
        ->assertDontSee('$14.00', escape: false);
});

test('a pricing employee cannot create a batch on an unrelated route', function () {
    $employee = User::create([
        'name' => 'مسعّر محلي',
        'email' => 'local-pricer@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $this->dubai->id,
        'capabilities' => [Capability::PriceShipments->value],
    ]);
    $aleppo = Warehouse::create(['name' => 'حلب', 'location' => 'سوريا']);
    $homs = Warehouse::create(['name' => 'حمص', 'location' => 'سوريا']);
    $unrelatedRoute = Route::create([
        'name' => 'حلب ← حمص',
        'origin_warehouse_id' => $aleppo->id,
        'destination_warehouse_id' => $homs->id,
    ]);

    $this->actingAs($employee);

    Livewire::test(CreateBatch::class)
        ->fillForm(['route_id' => $unrelatedRoute->id])
        ->call('create')
        ->assertHasFormErrors(['route_id']);

    expect(Batch::where('route_id', $unrelatedRoute->id)->exists())->toBeFalse();
});

test('a pricing employee cannot move a batch onto an unrelated route', function () {
    $employee = User::create([
        'name' => 'مسعّر محلي',
        'email' => 'edit-pricer@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $this->dubai->id,
        'capabilities' => [Capability::PriceShipments->value],
    ]);
    $aleppo = Warehouse::create(['name' => 'حلب', 'location' => 'سوريا']);
    $homs = Warehouse::create(['name' => 'حمص', 'location' => 'سوريا']);
    $unrelatedRoute = Route::create([
        'name' => 'حلب ← حمص',
        'origin_warehouse_id' => $aleppo->id,
        'destination_warehouse_id' => $homs->id,
    ]);
    $batch = phaseFourBatch($this->route);

    $this->actingAs($employee);

    Livewire::test(EditBatch::class, ['record' => $batch->getRouteKey()])
        ->fillForm(['route_id' => $unrelatedRoute->id])
        ->call('save')
        ->assertHasFormErrors(['route_id']);

    expect($batch->fresh()->route_id)->toBe($this->route->id);
});
