<?php

use App\Enums\UserRole;
use App\Filament\Resources\Shipments\Pages\ViewShipment;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $warehouse = Warehouse::create(['name' => 'دبي', 'location' => 'الإمارات']);
    $this->admin = User::create([
        'name' => 'مدير',
        'email' => 'ship-ui@hmcargo.test',
        'password' => 'secret',
        'role' => UserRole::Administrator,
        'warehouse_id' => $warehouse->id,
    ]);
    $customer = Customer::create(['name' => 'عميل', 'phone' => '+971500000001']);
    $this->shipment = Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'مستلم',
        'recipient_phone' => '+963900000001',
    ]);
    $this->shipment->packages()->create(['weight_kg' => '1.0000']);
    $this->actingAs($this->admin);
});

test('the shipment view offers a print-labels action and shows a QR', function () {
    Livewire::test(ViewShipment::class, ['record' => $this->shipment->getRouteKey()])
        ->assertSee('طباعة الملصقات', escape: false)
        ->assertSee('<svg', escape: false)
        ->assertSee(route('labels.shipment', $this->shipment));
});
