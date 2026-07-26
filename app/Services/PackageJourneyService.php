<?php

namespace App\Services;

use App\Enums\PackageStatus;
use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Package;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PackageJourneyService
{
    /**
     * @param  array<int, int|string>  $packageIds
     */
    public function advance(
        Shipment $shipment,
        array $packageIds,
        User $actor,
        string $source = 'journey_progress',
        ?string $note = null,
        ?string $publicReason = null,
    ): Shipment {
        return DB::transaction(function () use ($shipment, $packageIds, $actor, $source, $note, $publicReason): Shipment {
            [$lockedShipment, $route, $packages] = $this->lockedSelection($shipment, $packageIds, $actor);

            foreach ($packages as $package) {
                $this->ensureProgressable($package);

                $previous = $package->status;
                $next = $this->nextStatus($previous);
                $warehouseId = $this->authorizedWarehouseIdFor($route, $next, $actor);

                $package->forceFill([
                    'status' => $next,
                    'is_delayed' => false,
                    'delay_reason' => null,
                    'delay_reason_is_public' => false,
                    'delayed_at' => null,
                ])->save();

                DB::table('package_status_events')->insert([
                    'package_id' => $package->id,
                    'previous_status' => $previous->value,
                    'status' => $next->value,
                    'event_kind' => 'progress',
                    'warehouse_id' => $warehouseId,
                    'user_id' => $actor->id,
                    'scanned_at' => now(),
                    'source' => $source,
                    'note' => $note,
                    'private_reason' => null,
                    'public_reason' => $publicReason,
                ]);
            }

            $lockedShipment->recalculateOperationalStatus();

            return $lockedShipment->fresh();
        });
    }

    /**
     * @param  array<int, int|string>  $packageIds
     */
    public function delay(
        Shipment $shipment,
        array $packageIds,
        User $actor,
        string $reason,
        bool $publishReason,
    ): Shipment {
        $reason = trim($reason);

        if ($reason === '') {
            throw new DomainException('يجب إدخال السبب.');
        }

        return DB::transaction(function () use ($shipment, $packageIds, $actor, $reason, $publishReason): Shipment {
            [$lockedShipment, $route, $packages] = $this->lockedSelection($shipment, $packageIds, $actor);

            foreach ($packages as $package) {
                $this->ensureDelayable($package);
                $warehouseId = $this->authorizedWarehouseIdForCurrentStatus($route, $package->status, $actor);

                $package->forceFill([
                    'is_delayed' => true,
                    'delay_reason' => $reason,
                    'delay_reason_is_public' => $publishReason,
                    'delayed_at' => now(),
                ])->save();

                DB::table('package_status_events')->insert([
                    'package_id' => $package->id,
                    'previous_status' => $package->status->value,
                    'status' => $package->status->value,
                    'event_kind' => 'delay',
                    'warehouse_id' => $warehouseId,
                    'user_id' => $actor->id,
                    'scanned_at' => now(),
                    'source' => 'journey_delay',
                    'note' => null,
                    'private_reason' => $publishReason ? null : $reason,
                    'public_reason' => $publishReason ? $reason : null,
                ]);
            }

            return $lockedShipment->fresh();
        });
    }

    /**
     * @param  array<int, int|string>  $packageIds
     */
    public function addNote(
        Shipment $shipment,
        array $packageIds,
        User $actor,
        string $publicReason,
    ): Shipment {
        $publicReason = trim($publicReason);

        if ($publicReason === '') {
            throw new DomainException('يجب إدخال نص الرسالة.');
        }

        return DB::transaction(function () use ($shipment, $packageIds, $actor, $publicReason): Shipment {
            [$lockedShipment, $route, $packages] = $this->lockedSelection($shipment, $packageIds, $actor);

            foreach ($packages as $package) {
                $warehouseId = $this->authorizedWarehouseIdForCurrentStatus($route, $package->status, $actor);

                DB::table('package_status_events')->insert([
                    'package_id' => $package->id,
                    'previous_status' => $package->status->value,
                    'status' => $package->status->value,
                    'event_kind' => 'note',
                    'warehouse_id' => $warehouseId,
                    'user_id' => $actor->id,
                    'scanned_at' => now(),
                    'source' => 'journey_note',
                    'note' => null,
                    'private_reason' => null,
                    'public_reason' => $publicReason,
                ]);
            }

            return $lockedShipment->fresh();
        });
    }

    /**
     * @param  array<int, int|string>  $packageIds
     */
    public function correct(
        Shipment $shipment,
        array $packageIds,
        PackageStatus $target,
        User $administrator,
        string $reason,
        bool $publishReason,
    ): Shipment {
        $reason = trim($reason);

        if (! $administrator->isAdministrator()) {
            throw new DomainException('تصحيح رحلة الطرد يتطلب صلاحية المدير.');
        }

        if ($reason === '') {
            throw new DomainException('يجب إدخال السبب.');
        }

        if ($target->journeyPosition() === null) {
            throw new DomainException('مرحلة التصحيح يجب أن تكون من مراحل الرحلة.');
        }

        return DB::transaction(function () use ($shipment, $packageIds, $administrator, $target, $reason, $publishReason): Shipment {
            [$lockedShipment, $route, $packages] = $this->lockedSelection($shipment, $packageIds, $administrator);
            $warehouseId = $this->authorizedWarehouseIdFor($route, $target, $administrator);

            foreach ($packages as $package) {
                $previous = $package->status;

                if ($previous->journeyPosition() === null && $previous !== PackageStatus::Created) {
                    throw new DomainException("الطرد {$package->barcode} ليس في مرحلة رحلة قابلة للتصحيح.");
                }

                $package->forceFill([
                    'status' => $target,
                    'is_delayed' => false,
                    'delay_reason' => null,
                    'delay_reason_is_public' => false,
                    'delayed_at' => null,
                ])->save();

                DB::table('package_status_events')->insert([
                    'package_id' => $package->id,
                    'previous_status' => $previous->value,
                    'status' => $target->value,
                    'event_kind' => 'correction',
                    'warehouse_id' => $warehouseId,
                    'user_id' => $administrator->id,
                    'scanned_at' => now(),
                    'source' => 'journey_correction',
                    'note' => null,
                    'private_reason' => $publishReason ? null : $reason,
                    'public_reason' => $publishReason ? $reason : null,
                ]);

                AuditLog::create([
                    'user_id' => $administrator->id,
                    'action' => 'package_journey_corrected',
                    'auditable_type' => Package::class,
                    'auditable_id' => $package->id,
                    'before' => ['status' => $previous->value],
                    'after' => ['status' => $target->value],
                    'reason' => $reason,
                    'created_at' => now(),
                ]);
            }

            $lockedShipment->recalculateOperationalStatus();

            return $lockedShipment->fresh();
        });
    }

    /**
     * @param  array<int, int|string>  $packageIds
     * @return array{0: Shipment, 1: Route, 2: Collection<int, Package>}
     */
    private function lockedSelection(Shipment $shipment, array $packageIds, User $actor): array
    {
        $lockedShipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
        $route = $lockedShipment->batch?->route;

        if (! $route) {
            throw new DomainException('الشحنة غير مرتبطة برحلة ذات مسار مضبوط.');
        }

        $packages = $lockedShipment->packages()
            ->whereKey($packageIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $this->authorizeSelection($lockedShipment, $packages, $packageIds, $actor, $route);

        return [$lockedShipment, $route, $packages];
    }

    /**
     * @param  Collection<int, Package>  $packages
     * @param  array<int, int|string>  $packageIds
     */
    private function authorizeSelection(
        Shipment $shipment,
        Collection $packages,
        array $packageIds,
        User $actor,
        Route $route,
    ): void {
        $selectedIds = collect($packageIds)
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->sort()
            ->values();

        if ($selectedIds->isEmpty()) {
            throw new DomainException('يجب اختيار طرد واحد على الأقل.');
        }

        $actualIds = $packages->modelKeys();
        sort($actualIds);

        if ($selectedIds->all() !== $actualIds) {
            throw new DomainException('الطرود المحددة لا تطابق طرود هذه الشحنة.');
        }

        if (! $actor->canUseRoute($route)) {
            throw new DomainException('المستخدم غير مخوّل للعمل على مسار هذه الشحنة.');
        }

        foreach ($packages as $package) {
            if ($package->shipment_id !== $shipment->id) {
                throw new DomainException('الطرود المحددة لا تطابق طرود هذه الشحنة.');
            }

            if ($package->status->journeyPosition() === null && $package->status !== PackageStatus::Created && ! $package->status->isException()) {
                throw new DomainException("الطرد {$package->barcode} ليس في مرحلة رحلة قابلة للتعديل.");
            }
        }
    }

    private function ensureProgressable(Package $package): void
    {
        if ($package->status->isException() || in_array($package->status, [
            PackageStatus::Cancelled,
            PackageStatus::Collected,
        ], true)) {
            throw new DomainException("الطرد {$package->barcode} في حالة لا تسمح بالتقديم.");
        }
    }

    private function ensureDelayable(Package $package): void
    {
        if ($package->status->isException() || in_array($package->status, [
            PackageStatus::Cancelled,
            PackageStatus::Collected,
        ], true) || $package->status->journeyPosition() === null) {
            throw new DomainException("الطرد {$package->barcode} في حالة لا تسمح بتسجيل تأخير.");
        }
    }

    private function nextStatus(PackageStatus $status): PackageStatus
    {
        if ($status === PackageStatus::Created) {
            return PackageStatus::ReceivedOrigin;
        }

        $position = $status->journeyPosition();
        $steps = PackageStatus::journeySteps();

        if ($position === null || ! isset($steps[$position + 1])) {
            throw new DomainException('حالة الطرد الحالية لا تسمح بالتقديم للمرحلة التالية.');
        }

        return $steps[$position + 1];
    }

    private function authorizedWarehouseIdFor(Route $route, PackageStatus $target, User $actor): int
    {
        $warehouseId = match ($target) {
            PackageStatus::ReceivedOrigin,
            PackageStatus::ArrivedOriginAirport,
            PackageStatus::InTransit => $route->origin_warehouse_id,

            PackageStatus::ArrivedTransit,
            PackageStatus::DepartedTransit => $route->transit_warehouse_id ?? $route->destination_warehouse_id,

            PackageStatus::ArrivedDestination,
            PackageStatus::Collected => $route->destination_warehouse_id,

            default => throw new DomainException('مرحلة الرحلة المطلوبة غير مدعومة.'),
        };

        if (! $actor->canAccessWarehouse($warehouseId)) {
            throw new DomainException('المستخدم غير مخوّل لتنفيذ هذه المرحلة من هذا المستودع.');
        }

        return $warehouseId;
    }

    private function authorizedWarehouseIdForCurrentStatus(Route $route, PackageStatus $status, User $actor): int
    {
        $warehouseId = match ($status) {
            PackageStatus::ReceivedOrigin,
            PackageStatus::ArrivedOriginAirport,
            PackageStatus::InTransit => $route->origin_warehouse_id,

            PackageStatus::ArrivedTransit,
            PackageStatus::DepartedTransit => $route->transit_warehouse_id ?? $route->destination_warehouse_id,

            PackageStatus::ArrivedDestination,
            PackageStatus::Collected => $route->destination_warehouse_id,

            default => throw new DomainException('مرحلة الرحلة المطلوبة غير مدعومة.'),
        };

        if (! $actor->canAccessWarehouse($warehouseId)) {
            throw new DomainException('المستخدم غير مخوّل لتنفيذ هذه المرحلة من هذا المستودع.');
        }

        return $warehouseId;
    }

    public function advanceBatch(
        Batch $batch,
        User $actor,
        string $source = 'batch_journey_progress',
        ?string $note = null,
        ?string $publicReason = null,
    ): Batch {
        return DB::transaction(function () use ($batch, $actor, $source, $note, $publicReason): Batch {
            $lockedBatch = Batch::query()->lockForUpdate()->findOrFail($batch->id);
            $shipments = $lockedBatch->shipments()->with('packages')->get();

            if ($shipments->isEmpty()) {
                throw new DomainException('لا توجد شحنات في هذه الرحلة.');
            }

            $maxJourneyPosition = count(PackageStatus::journeySteps()) - 1;

            $advancedCount = 0;
            foreach ($shipments as $shipment) {
                $progressablePackageIds = $shipment->packages
                    ->filter(fn (Package $p) => $p->status === PackageStatus::Created
                        || ($p->status->journeyPosition() !== null && $p->status->journeyPosition() < $maxJourneyPosition))
                    ->pluck('id')
                    ->all();

                if (! empty($progressablePackageIds)) {
                    $this->advance($shipment, $progressablePackageIds, $actor, $source, $note, $publicReason);
                    $advancedCount++;
                }
            }

            if ($advancedCount === 0) {
                throw new DomainException('لا توجد طرود قابلة للتقديم في هذه الرحلة.');
            }

            return $lockedBatch->fresh();
        });
    }

    public function delayBatch(
        Batch $batch,
        User $actor,
        string $reason,
        bool $publishReason,
    ): Batch {
        return DB::transaction(function () use ($batch, $actor, $reason, $publishReason): Batch {
            $lockedBatch = Batch::query()->lockForUpdate()->findOrFail($batch->id);
            $shipments = $lockedBatch->shipments()->with('packages')->get();

            if ($shipments->isEmpty()) {
                throw new DomainException('لا توجد شحنات في هذه الرحلة.');
            }

            $delayedCount = 0;
            foreach ($shipments as $shipment) {
                $delayablePackageIds = $shipment->packages
                    ->filter(fn (Package $p) => $p->status->journeyPosition() !== null
                        && ! $p->status->isException()
                        && ! in_array($p->status, [PackageStatus::Cancelled, PackageStatus::Collected], true))
                    ->pluck('id')
                    ->all();

                if (! empty($delayablePackageIds)) {
                    $this->delay($shipment, $delayablePackageIds, $actor, $reason, $publishReason);
                    $delayedCount++;
                }
            }

            if ($delayedCount === 0) {
                throw new DomainException('لا توجد طرود قابلة لتسجيل التأخير في هذه الرحلة.');
            }

            return $lockedBatch->fresh();
        });
    }

    public function correctBatch(
        Batch $batch,
        PackageStatus $target,
        User $administrator,
        string $reason,
        bool $publishReason,
    ): Batch {
        return DB::transaction(function () use ($batch, $target, $administrator, $reason, $publishReason): Batch {
            $lockedBatch = Batch::query()->lockForUpdate()->findOrFail($batch->id);
            $shipments = $lockedBatch->shipments()->with('packages')->get();

            if ($shipments->isEmpty()) {
                throw new DomainException('لا توجد شحنات في هذه الرحلة.');
            }

            $correctedCount = 0;
            foreach ($shipments as $shipment) {
                $correctablePackageIds = $shipment->packages
                    ->filter(fn (Package $p) => $p->status === PackageStatus::Created || $p->status->journeyPosition() !== null)
                    ->pluck('id')
                    ->all();

                if (! empty($correctablePackageIds)) {
                    $this->correct($shipment, $correctablePackageIds, $target, $administrator, $reason, $publishReason);
                    $correctedCount++;
                }
            }

            if ($correctedCount === 0) {
                throw new DomainException('لا توجد طرود قابلة للتصحيح في هذه الرحلة.');
            }

            return $lockedBatch->fresh();
        });
    }
}
