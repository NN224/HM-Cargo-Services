<?php

namespace App\Filament\Resources\Warehouses\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class WarehousesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم المستودع')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location')
                    ->label('الموقع')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('أُنشئ في')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('الحالة'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            // No delete or bulk-delete action. Operational records are never
            // hard-deleted; deactivate via the is_active toggle instead.
            ->toolbarActions([]);
    }
}
