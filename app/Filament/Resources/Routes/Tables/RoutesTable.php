<?php

namespace App\Filament\Resources\Routes\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RoutesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم المسار')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('originWarehouse.name')
                    ->label('المنشأ'),

                TextColumn::make('transitWarehouse.name')
                    ->label('العبور')
                    ->placeholder('مباشر'),

                TextColumn::make('destinationWarehouse.name')
                    ->label('الوجهة'),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('الحالة'),
            ])
            ->defaultSort('name')
            ->recordActions([EditAction::make()])
            // Routes are referenced by historical shipments; deactivate instead.
            ->toolbarActions([]);
    }
}
