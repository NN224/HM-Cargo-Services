<?php

namespace App\Services;

use App\Enums\BatchStatus;
use App\Enums\Capability;
use App\Models\Batch;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Captures the company's route cost at the moment a batch leaves.
 *
 * Cost and status share one transaction because a dispatched batch without a
 * cost snapshot would make its profit unknowable, while a cost written onto
 * an open batch could still be replaced by an ordinary edit.
 */
class BatchDispatchService
{
    /** @throws DomainException when the batch has already left */
    public function dispatch(Batch $batch, int $costPerKgCents): Batch
    {
        if ($costPerKgCents < 0) {
            // Zero is an explicit cost snapshot, not a missing value. The
            // profitability rule distinguishes null from zero deliberately.
            throw new DomainException('تكلفة الكيلو لا يمكن أن تكون سالبة.');
        }

        return DB::transaction(function () use ($batch, $costPerKgCents): Batch {
            // A fresh locked row prevents two operators from replacing the
            // same commercial snapshot during simultaneous dispatch clicks.
            $batch = Batch::lockForUpdate()->findOrFail($batch->id);

            $this->guardUserCanDispatch($batch);

            if ($batch->status !== BatchStatus::Open) {
                throw new DomainException(
                    "الرحلة {$batch->reference} أُرسلت أو أُغلقت بالفعل، ولا يمكن تغيير تكلفة الإرسال."
                );
            }

            $batch->forceFill([
                'cost_per_kg_cents' => $costPerKgCents,
                'status' => BatchStatus::Dispatched,
                'dispatched_on' => today(),
            ])->save();

            return $batch;
        });
    }

    private function guardUserCanDispatch(Batch $batch): void
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->hasCapability(Capability::PriceShipments)) {
            throw new DomainException('ليس لديك صلاحية تسعير الشحنات أو إرسال الرحلات.');
        }

        $route = $batch->route;
        $hasWarehouseAccess = $user->canAccessWarehouse($route->origin_warehouse_id)
            || $user->canAccessWarehouse($route->transit_warehouse_id)
            || $user->canAccessWarehouse($route->destination_warehouse_id);

        if (! $hasWarehouseAccess) {
            throw new DomainException('هذه الرحلة لا تمر عبر المستودع المعيّن لك.');
        }
    }
}
