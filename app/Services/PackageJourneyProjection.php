<?php

namespace App\Services;

use App\Enums\PackageStatus;
use App\Models\Route;
use App\Models\Shipment;

class PackageJourneyProjection
{
    /**
     * @return array{steps: array<int, array{status: string, label: string, completed_count: int, current_count: int, delayed_count: int}>, package_count: int, delayed_count: int}
     */
    public function forShipment(Shipment $shipment): array
    {
        $shipment->loadMissing([
            'batch.route.originWarehouse',
            'packages',
        ]);

        $route = $shipment->batch?->route;
        $packages = $shipment->packages
            ->filter(fn ($package): bool => $package->status->journeyPosition() !== null && $package->status->isActive())
            ->values();

        $steps = [];

        foreach (PackageStatus::journeySteps() as $position => $status) {
            $currentPackages = $packages->filter(
                fn ($package): bool => $package->status->journeyPosition() === $position
            );

            $steps[] = [
                'status' => $status->value,
                'label' => $this->labelFor($route, $status),
                'completed_count' => $packages
                    ->filter(fn ($package): bool => $package->status->journeyPosition() > $position)
                    ->count(),
                'current_count' => $currentPackages->count(),
                'delayed_count' => $currentPackages
                    ->filter(fn ($package): bool => (bool) $package->is_delayed)
                    ->count(),
            ];
        }

        return [
            'steps' => $steps,
            'package_count' => $packages->count(),
            'delayed_count' => $packages
                ->filter(fn ($package): bool => (bool) $package->is_delayed)
                ->count(),
        ];
    }

    public function labelFor(?Route $route, PackageStatus $status): string
    {
        return match ($status) {
            PackageStatus::ReceivedOrigin => 'وصلت مستودع '.$this->safeLabel($route?->originWarehouse?->name),
            PackageStatus::ArrivedOriginAirport => 'وصلت '.$this->safeLabel($route?->origin_airport_name),
            PackageStatus::InTransit => 'غادرت '.$this->safeLabel($route?->origin_airport_name),
            PackageStatus::ArrivedTransit => 'وصلت '.$this->safeLabel($route?->destination_airport_name),
            PackageStatus::DepartedTransit => 'غادرت '.$this->safeLabel($route?->destination_airport_name),
            PackageStatus::ArrivedDestination => 'وصلت '.$this->safeLabel($route?->delivery_office_name),
            PackageStatus::Collected => 'استلمها العميل',
            default => 'غير مضبوط',
        };
    }

    private function safeLabel(?string $value): string
    {
        return filled($value) ? $value : 'غير مضبوط';
    }
}
