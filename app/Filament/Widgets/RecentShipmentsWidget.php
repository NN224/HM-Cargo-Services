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
    protected static ?int $sort = 3;

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
                Shipment::query()->latest()->limit(8)
            )
            ->columns([
                TextColumn::make('reference')->label('رقم الشحنة'),
                TextColumn::make('customer.name')->label('العميل'),
                TextColumn::make('recipient_name')->label('المستلم'),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (ShipmentStatus $state): string => $state->label()),
            ])
            ->paginated(false);
    }
}
