<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم العميل')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable(),

                IconColumn::make('is_credit_customer')
                    ->label('آجل')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_credit_customer')->label('عميل آجل'),
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
            // Customers are deactivated, never hard-deleted (D-018).
            ->toolbarActions([]);
    }
}
