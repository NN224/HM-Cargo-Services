<?php

use App\Models\Customer;
use App\Models\Shipment;

test('trackingUrl points at the public tracking route for this barcode', function () {
    $customer = Customer::create(['name' => 'عميل', 'phone' => '+971500000001']);
    $shipment = Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'مستلم',
        'recipient_phone' => '+963900000001',
    ]);
    $package = $shipment->packages()->create(['weight_kg' => '1.0000']);

    expect($package->trackingUrl())->toBe(route('tracking.show', $package->barcode));
});
