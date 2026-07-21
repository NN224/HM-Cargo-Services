<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Shipment;
use App\Services\PaymentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Convert input dollars to cents
        $data['amount_cents'] = (int) round($data['amount'] * 100);
        unset($data['amount']);

        $data['collected_by'] = auth()->id();

        // An employee collects at their own warehouse; an administrator has
        // none, so they pick one on the form. Copying the user's warehouse
        // unconditionally meant an administrator could not record a payment
        // at all — the insert failed on a not-null constraint.
        $data['warehouse_id'] = auth()->user()->warehouse_id ?? ($data['warehouse_id'] ?? null);

        $targetShipment = null;
        if (!empty($data['shipment_id'])) {
            $targetShipment = Shipment::find($data['shipment_id']);
        }
        unset($data['shipment_id']);

        $service = app(PaymentService::class);
        
        return $service->recordPayment($data, $targetShipment);
    }
}
