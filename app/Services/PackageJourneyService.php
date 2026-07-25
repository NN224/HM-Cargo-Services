<?php

namespace App\Services;

use App\Enums\PackageStatus;
use App\Models\AuditLog;
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
    ): Shipment {
        return DB::transaction(function () use ($shipment, $packageIds, $actor, $source, $note): Shipment {
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
                    'public_reason' => null,
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

                if ($previous->journeyPosition() === null) {
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

            if ($package->status->journeyPosition() === null && ! $package->status->isException()) {
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
}
