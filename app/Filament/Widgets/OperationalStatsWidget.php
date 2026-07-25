<?php

namespace App\Filament\Widgets;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * The two counts that ask an operator to do something: cargo received but not
 * yet on a batch (to price and load), and cargo that has all arrived (to
 * release). Shown to everyone — neither is money.
 */
class OperationalStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return auth()->user() instanceof User;
    }

    protected function getStats(): array
    {
        // "Awaiting a batch" — cargo received, not yet on a load, not
        // cancelled. The definition lives on the model so this count and the
        // scope cannot drift apart.
        $awaitingBatch = Shipment::query()->awaitingBatch()->count();

        $readyForCollection = Shipment::query()
            ->where('status', ShipmentStatus::ReadyForCollection->value)
            ->count();

        $inTransit = Shipment::query()
            ->where('status', ShipmentStatus::InTransit->value)
            ->count();

        return [
            Stat::make('بانتظار رحلة', (string) $awaitingBatch)
                ->description('شحنات تنتظر الإسناد إلى رحلة')
                ->icon(Heroicon::OutlinedArchiveBox)
                ->color($awaitingBatch > 0 ? 'warning' : 'gray'),
            Stat::make('في الطريق', (string) $inTransit)
                ->description('شحنات حُمِّلت وهي في الطريق')
                ->icon(Heroicon::OutlinedTruck)
                ->color('info'),
            Stat::make('جاهزة للتسليم', (string) $readyForCollection)
                ->description('وصلت كل طرودها — بانتظار الاستلام')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color($readyForCollection > 0 ? 'success' : 'gray'),
        ];
    }
}
