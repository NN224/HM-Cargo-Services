<?php

namespace App\Filament\Widgets;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Today's activity at a glance, without opening the full list. It carries no
 * money column — the recent-shipments view is operational, and money is gated
 * elsewhere; a charge does not belong on a screen everyone sees.
 */
class RecentShipmentsWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'أحدث الشحنات';

    public static function canView(): bool
    {
        return auth()->user() instanceof User;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Shipment::query()->with(['customer', 'batch.route'])->latest()->limit(10)
            )
            ->columns([
                TextColumn::make('reference')
                    ->label('رقم الشحنة')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->color('primary'),
                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->weight('semibold'),
                TextColumn::make('recipient_name')
                    ->label('المستلم')
                    ->color('gray'),
                TextColumn::make('batch.route.name')
                    ->label('خط الشحن')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (ShipmentStatus $state): string => $state->label())
                    ->color(fn (ShipmentStatus $state): string => match ($state) {
                        ShipmentStatus::Draft,
                        ShipmentStatus::AwaitingBatch => 'gray',
                        ShipmentStatus::Assigned,
                        ShipmentStatus::Pending => 'warning',
                        ShipmentStatus::InTransit,
                        ShipmentStatus::PartialAtTransit,
                        ShipmentStatus::AtTransit,
                        ShipmentStatus::PartialAtDestination => 'info',
                        ShipmentStatus::Arrived,
                        ShipmentStatus::ReadyForCollection => 'success',
                        ShipmentStatus::PartiallyCollected,
                        ShipmentStatus::Collected => 'primary',
                        ShipmentStatus::Cancelled => 'danger',
                        ShipmentStatus::Exception => 'danger',
                    }),
                TextColumn::make('created_at')
                    ->label('تاريخ الاستلام')
                    ->date('Y-m-d')
                    ->color('gray')
                    ->size('sm'),
            ])
            ->paginated(false);
    }
}
