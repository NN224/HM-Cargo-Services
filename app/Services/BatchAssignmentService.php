<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Shipment;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Assigns a shipment to a batch and prices it.
 *
 * This is where a shipment becomes billable (D-007): the batch supplies the
 * route, the route plus customer supplies the rate, and the rate is captured
 * on the shipment so that later rate changes can never rewrite history.
 *
 * The whole operation is one transaction. A half-assigned shipment — attached
 * to a batch but unpriced, or priced against the wrong route — would be worse
 * than a clean refusal.
 */
class BatchAssignmentService
{
    /**
     * @throws DomainException when the shipment cannot be priced
     */
    public function assign(Shipment $shipment, Batch $batch, bool $skipRateCheck = false): Shipment
    {
        return DB::transaction(function () use ($shipment, $batch, $skipRateCheck): Shipment {
            // Re-read inside the transaction so two operators assigning the
            // same shipment at once cannot both pass the checks below.
            $shipment = Shipment::lockForUpdate()->findOrFail($shipment->id);
            $batch = Batch::lockForUpdate()->findOrFail($batch->id);

            $this->guardShipmentIsFree($shipment);
            $this->guardBatchAcceptsShipments($batch);
            $this->guardShipmentHasWeight($shipment);
            $this->guardDestinationMatches($shipment, $batch);

            $rate = $skipRateCheck ? null : $this->resolveRate($shipment, $batch);

            $computed = $rate !== null ? $this->computeCharge($shipment, $rate) : 0;

            $shipment->forceFill([
                'batch_id' => $batch->id,
                'rate_per_kg_cents' => $rate,
                'computed_charge_cents' => $computed,
                // Until the operator decides otherwise, what is billed is what
                // was computed. D-020 makes rounding a deliberate act, not a
                // silent default.
                'final_charge_cents' => $computed,
                'priced_at' => now(),
            ])->save();

            return $shipment;
        });
    }

    private function guardShipmentIsFree(Shipment $shipment): void
    {
        if ($shipment->batch_id === null) {
            return;
        }

        // Only an active batch holds a shipment. One released by a cancelled
        // or completed batch may be assigned again.
        if ($shipment->batch?->status->isActive()) {
            throw new DomainException(
                "الشحنة {$shipment->reference} مرتبطة بالفعل بالرحلة "
                ."{$shipment->batch->reference}. لا يمكن إسنادها لأكثر من رحلة نشطة."
            );
        }
    }

    private function guardBatchAcceptsShipments(Batch $batch): void
    {
        if ($batch->status->isDispatched()) {
            throw new DomainException(
                "الرحلة {$batch->reference} غادرت بالفعل، ولا يمكن إضافة شحنات إليها."
            );
        }
    }

    private function guardShipmentHasWeight(Shipment $shipment): void
    {
        if ((float) $shipment->total_weight_kg <= 0) {
            throw new DomainException(
                "الشحنة {$shipment->reference} بلا وزن. أضف طروداً قبل تسعيرها."
            );
        }
    }

    private function guardDestinationMatches(Shipment $shipment, Batch $batch): void
    {
        // Legacy shipments might not have a destination warehouse.
        if ($shipment->destination_warehouse_id === null) {
            return;
        }

        if ($shipment->destination_warehouse_id !== $batch->route->destination_warehouse_id) {
            $shipmentDest = $shipment->destinationWarehouse->name;
            $batchDest = $batch->route->destinationWarehouse->name;
            throw new DomainException(
                "الشحنة متجهة إلى {$shipmentDest}، بينما مسار الرحلة ينتهي في {$batchDest}. لا يمكن إسنادها."
            );
        }
    }

    /**
     * The customer's agreed price per kilogram on this batch's route.
     *
     * Refuses rather than guessing. A missing rate is a commercial question
     * for a human, and inventing a fallback price would bill a real customer
     * an amount nobody agreed to.
     */
    private function resolveRate(Shipment $shipment, Batch $batch): int
    {
        $rate = $shipment->customer->rateForRoute($batch->route);

        if ($rate === null) {
            throw new DomainException(
                "لا يوجد سعر متفق عليه للعميل {$shipment->customer->name} "
                ."على المسار {$batch->route->name}. حدّد السعر أولاً."
            );
        }

        return $rate->rate_per_kg_cents;
    }

    /**
     * Exact weight multiplied by the rate, to the cent.
     *
     * bcmul keeps the multiplication in decimal. Doing this with PHP floats
     * reintroduces binary rounding error — 0.7 × 300 evaluates to 209.999...
     * and truncates to 209 instead of 210.
     */
    private function computeCharge(Shipment $shipment, int $ratePerKgCents): int
    {
        $weight = (string) $shipment->total_weight_kg;

        return (int) round((float) bcmul($weight, (string) $ratePerKgCents, 6));
    }
}
