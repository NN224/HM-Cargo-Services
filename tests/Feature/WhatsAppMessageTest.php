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

    // The customer (the sender) has their own phone; the intake message goes
    // here. The recipient may be a different person and phone; the arrival
    // message goes there. In the common case the recipient is the customer,
    // so both reach the same registered number.
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

// ------------------------------------------------------- unit: intake message

test('the intake message goes to the customer and carries the tracking link', function () {
    // Sent when the cargo is received — this is when the tracking link is
    // useful, and it goes to the sender (the customer) on their own number.
    $shipment = makeTestShipment('سامي', '+9613000001', 9250, 0,
        ShipmentStatus::Pending->value);

    $url = (new WhatsAppMessageService)->intakeUrl($shipment);

    expect($url)->toStartWith('https://wa.me/971500000001?text=') // the customer's phone
        ->and($url)->toContain(urlencode('أحمد'))                 // the customer's name
        ->and($url)->toContain(urlencode('استلمنا'))
        ->and($url)->toContain(urlencode('/track/'.$shipment->public_token))
        // No money at intake: nothing is due until the goods arrive.
        ->and($url)->not->toContain(urlencode('المبلغ'));
});

// ------------------------------------------------------ unit: arrival message

test('the arrival message goes to the recipient with the amount and no tracking link', function () {
    // Sent on arrival. Tracking is moot once it has arrived, so the link is
    // gone; what the recipient needs now is the amount and the invitation to
    // collect.
    $shipment = makeTestShipment('سامي', '+9613000001', 9250, 0,
        ShipmentStatus::ReadyForCollection->value);

    $url = (new WhatsAppMessageService)->arrivalUrl($shipment);

    expect($url)->toStartWith('https://wa.me/9613000001?text=') // the recipient's phone
        ->and($url)->toContain(urlencode('سامي'))
        ->and($url)->toContain(urlencode('وصلت'))
        ->and($url)->toContain(urlencode('دمشق'))
        ->and($url)->toContain(urlencode('92.50'))
        ->and($url)->not->toContain(urlencode('/track/')); // no tracking link at arrival
});

test('the arrival message includes the remaining amount when partially paid', function () {
    $shipment = makeTestShipment('كريم', '+96171112233', 15000, 5000,
        ShipmentStatus::ReadyForCollection->value);

    $url = (new WhatsAppMessageService)->arrivalUrl($shipment);

    expect($url)->toContain(urlencode('150.00'))
        ->and($url)->toContain(urlencode('100.00'));
});

test('the arrival message strips non-digits and plus from the phone', function () {
    $shipment = makeTestShipment('نور', '+961 71-112233', 5000, 0,
        ShipmentStatus::ReadyForCollection->value);

    $url = (new WhatsAppMessageService)->arrivalUrl($shipment);

    expect($url)->toStartWith('https://wa.me/96171112233?text=');
});

test('the messages url-encode the arabic text', function () {
    $shipment = makeTestShipment('عمر', '+9613000001', 5000, 0,
        ShipmentStatus::ReadyForCollection->value);

    $url = (new WhatsAppMessageService)->arrivalUrl($shipment);

    // Raw Arabic must not appear in the URL; the encoded form must.
    expect($url)->not->toContain('مرحباً')
        ->and($url)->not->toContain('وصلت')
        ->and($url)->toContain(urlencode('مرحباً'));
});

// ------------------------------------------------- ui: arrival table action

test('the arrival action appears once the shipment is ready for collection', function () {
    $shipment = makeTestShipment('ليلى', '+9613000001', 7500, 0,
        ShipmentStatus::ReadyForCollection->value, 'tok-readydel');

    Livewire::actingAs($this->admin)
        ->test(ListShipments::class)
        ->assertTableActionVisible('whatsappArrival', $shipment->id);
});

test('the arrival action is hidden while the shipment is still travelling', function () {
    $shipment = makeTestShipment('ماجد', '+9613000001', 3000, 0,
        ShipmentStatus::Pending->value, 'tok-pending');

    Livewire::actingAs($this->admin)
        ->test(ListShipments::class)
        ->assertTableActionHidden('whatsappArrival', $shipment->id);
});

test('the arrival action is hidden for a cancelled shipment', function () {
    $cancelled = makeTestShipment('ملغي', '+9613000001', 1000, 0,
        ShipmentStatus::Cancelled->value, 'tok-cancelled');

    Livewire::actingAs($this->admin)
        ->test(ListShipments::class)
        ->assertTableActionHidden('whatsappArrival', $cancelled->id);
});

// The staff list must never print the secret tracking token — the intake
// WhatsApp handoff (which carries it) lives on the labels page instead.
test('the shipments list does not print the tracking token in any whatsapp link', function () {
    $shipment = makeTestShipment('ماجد', '+9613000001', 3000, 0,
        ShipmentStatus::Pending->value, 'tok-secret-xyz');

    Livewire::actingAs($this->admin)
        ->test(ListShipments::class)
        ->assertDontSee('tok-secret-xyz');
});
