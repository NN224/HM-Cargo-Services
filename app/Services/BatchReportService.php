<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Models\Batch;
use Illuminate\Support\Facades\DB;

/**
 * Builds a batch report (product-spec 4.10).
 *
 * All financial figures are derived from ledger records; no column is a
 * writeable summary that could silently disagree with the source data.
 */
class BatchReportService
{
    /**
     * @return array{shipment_count: int, package_count: int, total_weight_kg: string, revenue_cents: int, collected_cents: int, outstanding_cents: int, cost_cents: ?string, profit_cents: ?string}
     */
    public function getReport(Batch $batch): array
    {
        $activeShipments = $batch->shipments()
            ->where('status', '!=', ShipmentStatus::Cancelled->value);

        $allShipments = $batch->shipments();

        $shipmentCount = (int) (clone $allShipments)->count();

        $packageCount = (int) $batch->shipments()
            ->join('packages', 'shipments.id', '=', 'packages.shipment_id')
            ->count();

        $totalWeight = number_format((float) (clone $activeShipments)
            ->sum(DB::raw('CAST(total_weight_kg AS DECIMAL(12,4))')), 4, '.', '');

        $revenueCents = (int) (clone $activeShipments)->sum('final_charge_cents');
        $collectedCents = (int) (clone $activeShipments)->sum('paid_amount_cents');
        $outstandingCents = $revenueCents - $collectedCents;

        if ($batch->cost_per_kg_cents === null) {
            return [
                'shipment_count' => $shipmentCount,
                'package_count' => $packageCount,
                'total_weight_kg' => $totalWeight,
                'revenue_cents' => $revenueCents,
                'collected_cents' => $collectedCents,
                'outstanding_cents' => $outstandingCents,
                'cost_cents' => null,
                'profit_cents' => null,
            ];
        }

        // bcmul matches how BatchAssignmentService computes the customer charge.
        // The weight sum was computed in SQL, then cast to string to keep bcmul
        // in the decimal space — no PHP float touches the multiplication.
        $rawCost = bcmul($totalWeight, (string) $batch->cost_per_kg_cents, 4);
        $costCents = bcadd($rawCost, '0.5', 0);
        $profitCents = bcsub((string) $revenueCents, $costCents, 0);

        return [
            'shipment_count' => $shipmentCount,
            'package_count' => $packageCount,
            'total_weight_kg' => $totalWeight,
            'revenue_cents' => $revenueCents,
            'collected_cents' => $collectedCents,
            'outstanding_cents' => $outstandingCents,
            'cost_cents' => $costCents,
            'profit_cents' => $profitCents,
        ];
    }
}
