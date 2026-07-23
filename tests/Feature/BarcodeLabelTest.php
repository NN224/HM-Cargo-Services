<?php

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Batch;
use App\Models\Route;
use Illuminate\Support\Facades\Gate;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->origin = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->destination = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);
    
    $this->customer = Customer::create([
        'name' => 'John Doe',
        'phone' => '123456789',
        'warehouse_id' => $this->origin->id,
    ]);

    $this->route = Route::create([
        'name' => 'DXB-DAM',
        'origin_warehouse_id' => $this->origin->id,
        'destination_warehouse_id' => $this->destination->id,
    ]);

    $this->batch = Batch::create([
        'route_id' => $this->route->id,
        'cost_per_kg_cents' => 100,
    ]);

    $this->shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'Jane Doe',
        'recipient_phone' => '987654321',
    ]);
    $this->shipment->forceFill(['batch_id' => $this->batch->id])->save();
    
    $this->shipment->setFinalCharge(50000);
    $this->shipment->forceFill(['computed_charge_cents' => 50000])->save();

    $this->package1 = Package::create([
        'shipment_id' => $this->shipment->id,
        'weight_kg' => '10.0000',
    ]);

    $this->package2 = Package::create([
        'shipment_id' => $this->shipment->id,
        'weight_kg' => '15.0000',
    ]);
});

it('prints a package label containing required data and NO pricing data', function () {
    $user = User::factory()->create(['role' => UserRole::Administrator]);
    
    Gate::define('view', fn (User $u, Shipment $s) => true);

    $response = actingAs($user)->get("/labels/packages/{$this->package1->id}");
    
    $response->assertOk();
    
    $response->assertSee($this->package1->barcode);
    $response->assertSee('1 من 2');
    $response->assertSee($this->shipment->reference);
    $response->assertSee($this->shipment->recipient_name);
    $response->assertSee($this->destination->name);
    
    $response->assertDontSee('500');
    $response->assertDontSee('50000');
    $response->assertDontSee('السعر');
    $response->assertDontSee('المبلغ');
});

it('prints multiple labels for a full shipment containing NO pricing data', function () {
    $user = User::factory()->create(['role' => UserRole::Administrator]);
    
    Gate::define('view', fn (User $u, Shipment $s) => true);

    $response = actingAs($user)->get("/labels/shipments/{$this->shipment->id}");
    
    $response->assertOk();
    
    $response->assertSee($this->package1->barcode);
    $response->assertSee('1 من 2');
    $response->assertSee($this->package2->barcode);
    $response->assertSee('2 من 2');
    $response->assertSee($this->shipment->reference);
    
    $response->assertDontSee('500'); 
    $response->assertDontSee('50000'); 
});

it('requires authentication to print labels', function () {
    $response = get("/labels/packages/{$this->package1->id}");
    $response->assertRedirect('/login');
});

it('enforces authorization policy on label printing', function () {
    $user = User::factory()->create(['role' => UserRole::WarehouseEmployee]);
    
    Gate::define('view', fn (User $u, Shipment $s) => false);

    $response = actingAs($user)->get("/labels/packages/{$this->package1->id}");
    
    $response->assertForbidden();
});
