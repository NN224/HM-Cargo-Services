<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Enums\Capability;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
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
                Action::make('toggleActive')
                    ->label(fn ($record): string => $record->is_active ? 'تعطيل' : 'تفعيل')
                    ->icon(fn ($record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record): string => $record->is_active ? 'تعطيل السجل' : 'تفعيل السجل')
                    ->modalDescription('السجلات تُعطَّل ولا تُحذف نهائياً، حفاظاً على السجل التاريخي.')
                    ->visible(fn (): bool => auth()->user()?->isAdministrator() ?? false)
                    ->action(fn ($record) => $record->update(['is_active' => ! $record->is_active])),
                Action::make('delete')
                    ->label('حذف')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (): bool => auth()->user()?->hasCapability(Capability::DeleteRecords) ?? false)
                    ->authorize(fn ($record): bool => auth()->user()?->hasCapability(Capability::DeleteRecords) ?? false)
                    ->requiresConfirmation()
                    ->modalHeading('حذف السجل')
                    ->modalDescription('هل أنت متأكد من حذف هذا السجل نهائياً؟ لا يمكن التراجع عن هذا الإجراء.')
                    ->action(function ($record): void {
                        try {
                            $record->deleteSafely();
                            Notification::make()
                                ->title('تم الحذف')
                                ->success()
                                ->send();
                        } catch (DomainException $e) {
                            Notification::make()
                                ->title('لا يمكن الحذف')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([]);
    }
}
