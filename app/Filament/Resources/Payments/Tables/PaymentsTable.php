<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Models\Payment;
use App\Services\PaymentService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('receipt_number')
                    ->label('رقم الإيصال')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount_cents')
                    ->label('المبلغ')
                    ->money('USD', divideBy: 100)
                    ->sortable(),

                TextColumn::make('method')
                    ->label('الطريقة')
                    ->formatStateUsing(fn (string $state, Payment $record) => match ($state) {
                        Payment::METHOD_CASH => 'نقد',
                        Payment::METHOD_WHISH => 'Whish',
                        Payment::METHOD_BANK => 'حوالة بنكية',
                        Payment::METHOD_OTHER => $record->custom_method_name ?? 'أخرى',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Payment::TYPE_PAYMENT => 'success',
                        Payment::TYPE_REVERSAL => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        Payment::TYPE_PAYMENT => 'دفعة',
                        Payment::TYPE_REVERSAL => 'عكس',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('collected_at')
                    ->label('تاريخ التحصيل')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('collector.name')
                    ->label('المحصِّل')
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('reverse')
                    ->label('عكس الدفعة')
                    ->color('danger')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('reason')
                            ->label('سبب العكس')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (Payment $record, array $data) {
                        $service = app(PaymentService::class);
                        $service->reversePayment($record, auth()->user(), $data['reason']);
                    })
                    // Only administrators can reverse payments, and only standard payments can be reversed (not reversals themselves, and not already reversed)
                    ->visible(fn (Payment $record) => auth()->user()->isAdministrator() &&
                        ! $record->isReversal() &&
                        ! Payment::where('reverses_payment_id', $record->id)->exists()
                    ),
            ])
            ->defaultSort('collected_at', 'desc');
    }
}
