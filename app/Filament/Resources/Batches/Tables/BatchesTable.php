<?php

namespace App\Filament\Resources\Batches\Tables;

use App\Enums\BatchStatus;
use App\Enums\Capability;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class BatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('رقم الرحلة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('route.name')
                    ->label('المسار')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('shipments_count')
                    ->label('الشحنات')
                    ->counts('shipments')
                    ->alignCenter(),

                TextColumn::make('total_weight')
                    ->label('الوزن')
                    // SQL owns decimal aggregation; PHP only appends the unit.
                    ->state(fn ($record) => $record->shipments()
                        ->where('status', '!=', 'cancelled')
                        ->sum(DB::raw('CAST(total_weight_kg AS DECIMAL(12,4))')))
                    ->formatStateUsing(fn ($state): string => (string) $state.' كغ'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (BatchStatus $state): string => $state->label())
                    ->color(fn (BatchStatus $state): string => match ($state) {
                        BatchStatus::Open => 'warning',
                        BatchStatus::Dispatched, BatchStatus::InTransit => 'info',
                        BatchStatus::Arrived => 'primary',
                        BatchStatus::Completed => 'success',
                        BatchStatus::Cancelled => 'danger',
                    }),

                TextColumn::make('dispatched_on')
                    ->label('تاريخ الإرسال')
                    ->date('Y-m-d')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(BatchStatus::options()),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                // Only an empty batch may go. One holding shipments carries
                // their pricing history, so deleteSafely() refuses.
                Action::make('delete')
                    ->label('حذف')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (): bool => auth()->user()?->hasCapability(Capability::DeleteRecords) ?? false)
                    ->authorize(fn ($record): bool => auth()->user()?->hasCapability(Capability::DeleteRecords) ?? false)
                    ->requiresConfirmation()
                    ->modalHeading('حذف الرحلة')
                    ->modalDescription('هل أنت متأكد من حذف هذه الرحلة نهائياً؟ لا يمكن التراجع عن هذا الإجراء.')
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
