<?php

namespace App\Services;

use App\Enums\PackageStatus;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Releases one complete shipment to its recipient. */
class ShipmentCollectionService
{
    public function collect(Shipment $shipment, Warehouse $warehouse, User $user): Shipment
    {
        Gate::forUser($user)->authorize('view', $warehouse);

        return DB::transaction(function () use ($shipment, $warehouse, $user): Shipment {
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
            $route = $shipment->batch?->route;

            if (! $route || $route->destination_warehouse_id !== $warehouse->id) {
                throw new DomainException('لا يمكن تسليم الشحنة من مستودع غير مستودع وجهتها.');
            }

            $packages = $shipment->packages()
                ->where('status', '!=', PackageStatus::Cancelled->value)
                ->lockForUpdate()
                ->get();

            // D-015 is intentionally all-or-nothing. Counting package facts
            // here prevents a stale shipment summary from opening the gate.
            if ($packages->isEmpty() || $packages->contains(
                fn ($package) => $package->status !== PackageStatus::ArrivedDestination
            )) {
                throw new DomainException(
                    'لا يمكن تسليم الشحنة قبل وصول جميع الطرود النشطة إلى مستودع الوجهة.'
                );
            }

            foreach ($packages as $package) {
                $package->forceFill(['status' => PackageStatus::Collected])->save();

                DB::table('package_status_events')->insert([
                    'package_id' => $package->id,
                    'status' => PackageStatus::Collected->value,
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $user->id,
                    'scanned_at' => now(),
                    'source' => 'collection',
                    'note' => null,
                ]);
            }

            $shipment->forceFill(['status' => ShipmentStatus::Collected])->save();

            return $shipment;
        });
    }

    /**
     * Release packages that reached the destination while the remaining
     * packages continue through their normal operational lifecycle (D-029).
     */
    public function collectPartially(Shipment $shipment, Warehouse $warehouse, User $user): Shipment
    {
        Gate::forUser($user)->authorize('view', $warehouse);

        return DB::transaction(function () use ($shipment, $warehouse, $user): Shipment {
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
            $route = $shipment->batch?->route;

            if (! $route || $route->destination_warehouse_id !== $warehouse->id) {
                throw new DomainException('لا يمكن تسليم الشحنة من مستودع غير مستودع وجهتها.');
            }

            $arrivedPackages = $shipment->packages()
                ->where('status', PackageStatus::ArrivedDestination->value)
                ->lockForUpdate()
                ->get();

            if ($arrivedPackages->isEmpty()) {
                throw new DomainException('لا توجد أي طرود واصلة لمستودع الوجهة لتسليمها جزئياً.');
            }

            foreach ($arrivedPackages as $package) {
                $package->forceFill(['status' => PackageStatus::Collected])->save();

                DB::table('package_status_events')->insert([
                    'package_id' => $package->id,
                    'status' => PackageStatus::Collected->value,
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $user->id,
                    'scanned_at' => now(),
                    'source' => 'partial_collection',
                    'note' => 'تسليم جزئي للطرود الواصلة',
                ]);
            }

            $shipment->recalculateOperationalStatus();

            return $shipment;
        });
    }
}
