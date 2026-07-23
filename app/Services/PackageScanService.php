<?php

namespace App\Services;

use App\Enums\BatchStatus;
use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Records one physical package arrival and derives its shipment stage.
 *
 * A barcode scan is the source of truth. Batch state only opens or closes the
 * checkpoint; changing the batch itself never pretends a parcel was seen.
 */
class PackageScanService
{
    public function scan(
        string $barcode,
        Warehouse $warehouse,
        User $user,
        string $source = 'manual',
        ?string $note = null,
    ): Package {
        Gate::forUser($user)->authorize('view', $warehouse);

        return DB::transaction(function () use ($barcode, $warehouse, $user, $source, $note): Package {
            $identity = Package::query()
                ->where('barcode', $this->normalizeScannedValue($barcode))
                ->first(['id', 'shipment_id']);

            if (! $identity) {
                throw new DomainException('لم يُعثر على طرد بهذا الباركود.');
            }

            // Collection locks the shipment before its packages. Keeping the
            // same order here avoids a PostgreSQL deadlock when scan and
            // collection requests arrive together.
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($identity->shipment_id);
            $package = Package::query()->lockForUpdate()->findOrFail($identity->id);

            if ($package->shipment_id !== $shipment->id) {
                throw new DomainException(
                    'تغيّر ارتباط الطرد بالشحنة أثناء المسح. أعد المحاولة.'
                );
            }

            if (in_array($package->status, [PackageStatus::Cancelled, PackageStatus::Collected], true)) {
                throw new DomainException('لا يمكن تسجيل وصول طرد ملغى أو مُسلَّم.');
            }

            if ($package->status === PackageStatus::Damaged) {
                throw new DomainException(
                    'هذا الطرد في حالة استثناء، ولا يمكن تغييرها بمسح وصول عادي.'
                );
            }

            $status = $this->arrivalStatus($package, $shipment, $warehouse);

            // Status is a derived operational fact. forceFill avoids the
            // silent mass-assignment discard that previously hid derived
            // updates elsewhere in this project.
            $package->forceFill(['status' => $status])->save();

            DB::table('package_status_events')->insert([
                'package_id' => $package->id,
                'status' => $status->value,
                'warehouse_id' => $warehouse->id,
                'user_id' => $user->id,
                'scanned_at' => now(),
                'source' => $source,
                'note' => $note,
            ]);

            $shipment->recalculateOperationalStatus();

            return $package->fresh();
        });
    }

    /**
     * A camera reading a package QR yields the tracking URL, not the bare code.
     * Reduce a `.../track/PKG-XXXX` value to its trailing segment so the same
     * code a customer scans also drives an arrival scan. A plain barcode passes
     * through unchanged.
     */
    private function normalizeScannedValue(string $raw): string
    {
        $value = trim($raw);

        if (str_contains($value, '/track/')) {
            $value = trim(substr($value, strrpos($value, '/') + 1));
        }

        return $value;
    }

    private function arrivalStatus(
        Package $package,
        Shipment $shipment,
        Warehouse $warehouse,
    ): PackageStatus {
        $batch = $shipment->batch;
        $route = $batch?->route;

        if (! $batch || ! $route) {
            throw new DomainException('الطرد غير مرتبط برحلة ذات مسار، ولا يمكن تسجيل وصوله.');
        }

        if ($batch->status === BatchStatus::Open) {
            throw new DomainException('الرحلة لم تُرسل بعد، ولا يمكن تسجيل وصول طرودها.');
        }

        if (in_array($batch->status, [BatchStatus::Completed, BatchStatus::Cancelled], true)) {
            throw new DomainException('الرحلة مغلقة أو ملغاة، ولا تقبل مسح وصول جديداً.');
        }

        if ($route->destination_warehouse_id === $warehouse->id) {
            if ($route->hasTransit() && ! in_array($package->status, [
                PackageStatus::DepartedTransit,
                PackageStatus::Missing,
            ], true)) {
                throw new DomainException(
                    'لا يمكن تسجيل وصول الطرد إلى الوجهة قبل مغادرته مستودع العبور.'
                );
            }

            if (! $route->hasTransit() && ! in_array($package->status, [
                PackageStatus::Received,
                PackageStatus::ReceivedOrigin,
                PackageStatus::InTransit,
                PackageStatus::Missing,
            ], true)) {
                throw new DomainException('حالة الطرد الحالية لا تسمح بتسجيل وصوله إلى الوجهة.');
            }

            return PackageStatus::ArrivedDestination;
        }

        if ($route->transit_warehouse_id === $warehouse->id) {
            if (! in_array($package->status, [
                PackageStatus::Received,
                PackageStatus::ReceivedOrigin,
                PackageStatus::InTransit,
                PackageStatus::Missing,
            ], true)) {
                throw new DomainException('حالة الطرد الحالية لا تسمح بتسجيل وصوله إلى العبور.');
            }

            return PackageStatus::ArrivedTransit;
        }

        throw new DomainException('هذا المستودع ليس محطة وصول في مسار الرحلة.');
    }
}
