<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),

                TextColumn::make('role')
                    ->label('الدور')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '')
                    ->sortable(),

                TextColumn::make('warehouse.name')
                    ->label('المستودع')
                    // An administrator may not have a warehouse, so we show a dash.
                    ->default('-')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('الدور')
                    ->options(UserRole::options()),
                TernaryFilter::make('is_active')->label('الحالة'),
            ])
            ->defaultSort('name')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    // Deactivation is the sanctioned removal path: AGENTS.md forbids
                    // hard-deleting operational records, so this replaces a delete
                    // button rather than sitting beside one.
                    Action::make('toggleActive')
                        ->label(fn ($record): string => $record->is_active ? 'تعطيل' : 'تفعيل')
                        ->icon(fn ($record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record): string => $record->is_active ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->modalHeading(fn ($record): string => $record->is_active ? 'تعطيل المستخدم' : 'تفعيل المستخدم')
                        ->modalDescription('المستخدمون يُعطَّلون ولا يُحذفون نهائياً، حفاظاً على السجل التاريخي.')
                        // Only an administrator reaches this screen anyway, but it's good practice.
                        ->visible(fn (): bool => auth()->user()?->isAdministrator() ?? false)
                        ->action(fn ($record) => $record->update(['is_active' => ! $record->is_active])),
                ])
                    ->label('إجراءات')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->button(),
            ])
            ->toolbarActions([]);
    }
}
