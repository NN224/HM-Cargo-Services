<?php

namespace App\Filament\Resources\CustomerRates\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('route.name')
                    ->label('المسار')
                    ->searchable()
                    ->sortable(),

                // Storage is cents; operators only ever see dollars.
                TextColumn::make('rate_per_kg_cents')
                    ->label('السعر لكل كيلوغرام')
                    ->formatStateUsing(fn (int $state): string => '$'.number_format($state / 100, 2))
                    ->sortable(),
            ])
            ->defaultSort('customer.name')
            ->recordActions([
                EditAction::make(),
                // A rate is pricing configuration, not a financial record: the
                // snapshot taken on the shipment preserves charge history, so
                // removing an obsolete rate is safe.
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }
}
