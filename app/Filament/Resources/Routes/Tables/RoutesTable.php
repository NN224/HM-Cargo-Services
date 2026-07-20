<?php

namespace App\Filament\Resources\Routes\Tables;

use Filament\Actions\Action;
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
            ->recordActions([
                EditAction::make(),
                // Deactivation is the sanctioned removal path: AGENTS.md forbids
                // hard-deleting operational records, so this replaces a delete
                // button rather than sitting beside one.
                Action::make('toggleActive')
                    ->label(fn ($record): string => $record->is_active ? 'تعطيل' : 'تفعيل')
                    ->icon(fn ($record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record): string => $record->is_active ? 'تعطيل السجل' : 'تفعيل السجل')
                    ->modalDescription('السجلات تُعطَّل ولا تُحذف نهائياً، حفاظاً على السجل التاريخي.')
                    ->visible(fn (): bool => auth()->user()?->isAdministrator() ?? false)
                    ->action(fn ($record) => $record->update(['is_active' => ! $record->is_active])),
            ])
            // Routes are referenced by historical shipments; deactivate instead.
            ->toolbarActions([]);
    }
}
