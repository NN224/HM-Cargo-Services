<?php

namespace App\Filament\Widgets;

use App\Enums\Capability;
use App\Enums\ShipmentStatus;
use App\Models\Batch;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BatchProfitabilityWidget extends StatsOverviewWidget
{
    public ?Batch $record = null;

    public static function canView(): bool
    {
        $user = auth()->user();

        // Cost and profit are commercial figures. The fixed pricing switch is
        // the narrow D-022 grant; administrators hold it implicitly.
        return $user instanceof User
            && $user->hasCapability(Capability::PriceShipments);
    }

    protected function getStats(): array
    {
        // The same widget serves the batch view and the discovered dashboard.
        // On the dashboard each batch keeps its own cost snapshot; combining
        // weights first would incorrectly apply one route's cost to another.
        $batches = $this->record
            ? collect([$this->record])
            : $this->dashboardBatches();

        $revenueCents = 0;
        $costCents = 0;
        $costIsAvailable = true;

        foreach ($batches as $batch) {
            $shipments = $batch->shipments()
                ->where('status', '!=', ShipmentStatus::Cancelled->value);

            $revenueCents += (int) (clone $shipments)->sum('final_charge_cents');

            if ($batch->cost_per_kg_cents === null) {
                $costIsAvailable = false;

                continue;
            }

            $weight = (string) (clone $shipments)
                ->sum(DB::raw('CAST(total_weight_kg AS DECIMAL(12,4))'));

            // bcmul preserves the exact decimal weight. Currency storage is
            // integer cents, so the sub-cent product is converted to its
            // nearest cent entirely in decimal space. This is not D-020
            // customer rounding: no operator-entered final charge is changed.
            $rawCostCents = bcmul($weight, (string) $batch->cost_per_kg_cents, 4);
            $costCents += (int) bcadd($rawCostCents, '0.5', 0);
        }

        if (! $costIsAvailable) {
            return [
                Stat::make('الإيراد', $this->formatUsd($revenueCents)),
                Stat::make('التكلفة', 'غير متاحة'),
                Stat::make('الربح', 'غير متاحة'),
            ];
        }

        $profitCents = $revenueCents - $costCents;

        return [
            Stat::make('الإيراد', $this->formatUsd($revenueCents)),
            Stat::make('التكلفة', $this->formatUsd($costCents)),
            Stat::make('الربح', $this->formatUsd($profitCents))
                ->color($profitCents >= 0 ? 'success' : 'danger'),
        ];
    }

    /** @return Collection<int, Batch> */
    private function dashboardBatches(): Collection
    {
        $user = auth()->user();
        $query = Batch::query()
            ->whereHas('shipments', fn ($shipments) => $shipments
                ->where('status', '!=', ShipmentStatus::Cancelled->value));

        if ($user instanceof User && ! $user->isAdministrator()) {
            $query->whereHas('route', fn ($route) => $route
                ->where(fn ($warehouses) => $warehouses
                    ->where('origin_warehouse_id', $user->warehouse_id ?? 0)
                    ->orWhere('transit_warehouse_id', $user->warehouse_id ?? 0)
                    ->orWhere('destination_warehouse_id', $user->warehouse_id ?? 0)));
        }

        return $query->get();
    }

    private function formatUsd(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return sprintf('%s$%d.%02d', $sign, intdiv($absolute, 100), $absolute % 100);
    }
}
