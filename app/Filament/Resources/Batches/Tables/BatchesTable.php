<?php

namespace App\Filament\Resources\Batches\Tables;

use App\Enums\BatchStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
            ])
            ->toolbarActions([]);
    }
}
