<?php

namespace App\Filament\Resources\Warehouses\Tables;

use Filament\Actions\Action;
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
            // No delete or bulk-delete action. Operational records are never
            // hard-deleted; deactivate via the is_active toggle instead.
            ->toolbarActions([]);
    }
}
