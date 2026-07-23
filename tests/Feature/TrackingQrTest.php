<?php

use App\Models\Customer;
use App\Models\Shipment;

test('the public tracking page shows a QR of the tracking URL', function () {
    $customer = Customer::create(['name' => 'عميل', 'phone' => '+971500000001']);
    $shipment = Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'مستلم كامل',
        'recipient_phone' => '+963900000001',
    ]);
    $shipment->packages()->create(['weight_kg' => '1.0000']);

    $this->get(route('tracking.show', $shipment->public_token))
        ->assertOk()
        ->assertSee('<svg', escape: false);
});
