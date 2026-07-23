<?php

use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Shipments\Pages\ListShipments;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\WhatsAppMessageService;
use Livewire\Livewire;

beforeEach(function () {
    $this->dubai = Warehouse::create(['name' => 'دبي', 'location' => 'الإمارات']);
    $this->damascus = Warehouse::create(['name' => 'دمشق', 'location' => 'سوريا']);

    $this->route = Route::create([
        'name' => 'دبي ← دمشق',
        'origin_warehouse_id' => $this->dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);

    $this->batch = Batch::create(['route_id' => $this->route->id]);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'wa-admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => $this->dubai->id,
    ]);

    $this->customer = Customer::create([
        'name' => 'أحمد', 'phone' => '+971500000001',
    ]);
});

function makeTestShipment(string $name, string $phone, int $charge, int $paid, string $status, string $token = 'abc123def456'): Shipment
{
    // Read the actual ids from the setup rather than assuming they are 1.
    // SQLite happened to hand out id 1 to the first customer and batch;
    // PostgreSQL does not, so a hard-coded 1 broke the foreign key there.
    return tap(Shipment::create([
        'customer_id' => test()->customer->id,
        'recipient_name' => $name,
        'recipient_phone' => $phone,
        'status' => $status,
    ]), function (Shipment $s) use ($charge, $paid, $token) {
        $s->forceFill([
            'batch_id' => test()->batch->id,
            'final_charge_cents' => $charge,
            'paid_amount_cents' => $paid,
            'public_token' => $token,
        ])->save();
    });
}

// ------------------------------------------------------------ unit: messages

test('whatsapp service builds a wa.me url with arabic message', function () {
    $shipment = makeTestShipment('سامي', '+9613000001', 9250, 0,
        ShipmentStatus::ReadyForCollection->value);

    $service = new WhatsAppMessageService;
    $url = $service->buildUrl($shipment);

    expect($url)->toStartWith('https://wa.me/9613000001?text=')
        ->and($url)->toContain(urlencode('سامي'))
        ->and($url)->toContain(urlencode($shipment->reference))
        ->and($url)->toContain(urlencode('دمشق'))
        ->and($url)->toContain(urlencode('92.50'))
        ->and($url)->toContain(urlencode('/track/'.$shipment->public_token));
});

test('whatsapp service includes remaining amount when partially paid', function () {
    $shipment = makeTestShipment('كريم', '+96171112233', 15000, 5000,
        ShipmentStatus::ReadyForCollection->value);

    $url = (new WhatsAppMessageService)->buildUrl($shipment);

    expect($url)->toContain(urlencode('150.00'))
        ->and($url)->toContain(urlencode('100.00'));
});

test('whatsapp service strips non-digits and plus from phone', function () {
    $shipment = makeTestShipment('نور', '+961 71-112233', 5000, 0,
        ShipmentStatus::ReadyForCollection->value);

    $url = (new WhatsAppMessageService)->buildUrl($shipment);

    expect($url)->toStartWith('https://wa.me/96171112233?text=');
});

test('whatsapp message encodes arabic text for url', function () {
    $shipment = makeTestShipment('عمر', '+9613000001', 5000, 0,
        ShipmentStatus::ReadyForCollection->value);

    $url = (new WhatsAppMessageService)->buildUrl($shipment);

    // Raw Arabic must not appear in URL
    expect($url)->not->toContain('مرحباً')
        ->and($url)->not->toContain('وصلت')
        // But URL-encoded form should
        ->and($url)->toContain(urlencode('مرحباً'));
});

// ----------------------------------------------------------- ui: table action

test('whatsapp action is visible when shipment is ready for collection', function () {
    $shipment = makeTestShipment('ليلى', '+9613000001', 7500, 0,
        ShipmentStatus::ReadyForCollection->value, 'tok-readydel');

    Livewire::actingAs($this->admin)
        ->test(ListShipments::class)
        ->assertTableActionVisible('whatsapp', $shipment->id);
});

test('whatsapp action is hidden when shipment is not ready for collection', function () {
    $justPending = makeTestShipment('ماجد', '+9613000001', 3000, 0,
        ShipmentStatus::Pending->value, 'tok-pending');

    Livewire::actingAs($this->admin)
        ->test(ListShipments::class)
        ->assertTableActionHidden('whatsapp', $justPending->id);
});

test('whatsapp action is hidden for cancelled shipments', function () {
    $cancelled = makeTestShipment('ملغي', '+9613000001', 1000, 0,
        ShipmentStatus::Cancelled->value, 'tok-cancelled');

    Livewire::actingAs($this->admin)
        ->test(ListShipments::class)
        ->assertTableActionHidden('whatsapp', $cancelled->id);
});
