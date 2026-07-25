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

    private function labelFor(?Route $route, PackageStatus $status): string
    {
        return match ($status) {
            PackageStatus::ReceivedOrigin => 'وصل مستودع '.$this->safeLabel($route?->originWarehouse?->name),
            PackageStatus::ArrivedOriginAirport => 'وصل '.$this->safeLabel($route?->origin_airport_name),
            PackageStatus::InTransit => 'غادر '.$this->safeLabel($route?->origin_airport_name),
            PackageStatus::ArrivedTransit => 'وصل '.$this->safeLabel($route?->destination_airport_name),
            PackageStatus::DepartedTransit => 'غادر '.$this->safeLabel($route?->destination_airport_name),
            PackageStatus::ArrivedDestination => 'وصل '.$this->safeLabel($route?->delivery_office_name),
            PackageStatus::Collected => 'استلمه العميل',
            default => 'غير مضبوط',
        };
    }

    private function safeLabel(?string $value): string
    {
        return filled($value) ? $value : 'غير مضبوط';
    }
}
