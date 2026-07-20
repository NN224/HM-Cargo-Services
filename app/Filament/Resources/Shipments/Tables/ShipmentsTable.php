<?php

namespace App\Filament\Resources\Shipments\Tables;

use App\Enums\ShipmentStatus;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShipmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('رقم الشحنة')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('recipient_name')
                    ->label('المستلم')
                    ->searchable(),

                TextColumn::make('packages_count')
                    ->label('الطرود')
                    ->counts('packages')
                    ->alignCenter(),

                TextColumn::make('total_weight_kg')
                    ->label('الوزن')
                    ->formatStateUsing(fn ($state): string => rtrim(rtrim(number_format((float) $state, 4), '0'), '.').' كغ')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (ShipmentStatus $state): string => $state->label())
                    ->color(fn (ShipmentStatus $state): string => match ($state) {
                        ShipmentStatus::Pending => 'warning',
                        ShipmentStatus::InTransit => 'info',
                        ShipmentStatus::Arrived => 'primary',
                        ShipmentStatus::Collected => 'success',
                        ShipmentStatus::Cancelled => 'danger',
                    }),

                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->date('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(ShipmentStatus::options()),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),

                // The public link is the only safe way to share a shipment:
                // it carries the random token, never the readable reference.
                Action::make('copyTrackingLink')
                    ->label('رابط التتبع')
                    ->icon('heroicon-o-link')
                    ->color('gray')
                    ->action(function ($record, $livewire): void {
                        $livewire->dispatch('copy-to-clipboard', text: url('/track/'.$record->public_token));
                    })
                    ->extraAttributes(fn ($record): array => [
                        'x-on:click' => 'navigator.clipboard.writeText('
                            .json_encode(url('/track/'.$record->public_token)).')',
                    ]),
            ])
            // Shipments are never hard-deleted: payments, packages and history
            // all hang off them (AGENTS.md).
            ->toolbarActions([]);
    }
}
