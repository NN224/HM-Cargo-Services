<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Models\Payment;
use App\Services\PaymentService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
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
                        // «عكس» on its own says nothing to anyone reading the
                        // list. This row exists because an earlier one was
                        // wrong; say that.
                        Payment::TYPE_REVERSAL => 'تصحيح',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('reversal_reason')
                    ->label('سبب التصحيح')
                    ->placeholder('—')
                    ->wrap(),

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
                // Named for the situation the operator is in — "I recorded
                // this wrong" — rather than for what bookkeeping calls the
                // remedy. The person at the counter is not an accountant.
                Action::make('reverse')
                    ->label('تصحيح دفعة خاطئة')
                    ->color('danger')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->modalHeading('تصحيح دفعة خاطئة')
                    ->modalDescription(fn (Payment $record): string => sprintf(
                        'ستُسجَّل دفعة مقابلة بمبلغ %s تُلغي أثر هذه الدفعة على حساب %s، '
                        .'فيعود المبلغ مستحقاً عليه من جديد. '
                        .'الدفعة الأصلية لا تُحذف — تبقى الاثنتان ظاهرتين في كشف الحساب، '
                        .'لأن السجل الذي يخفي الخطأ لا يُوثق به.',
                        '$'.number_format($record->amount_cents / 100, 2),
                        $record->customer->name,
                    ))
                    ->modalSubmitActionLabel('سجّل التصحيح')
                    ->form([
                        Textarea::make('reason')
                            ->label('سبب التصحيح')
                            ->helperText('مثال: سُجّلت على العميل الخطأ · المبلغ غير صحيح · العميل لم يدفع فعلياً.')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (Payment $record, array $data) {
                        $service = app(PaymentService::class);
                        $reversal = $service->reversePayment($record, auth()->user(), $data['reason']);

                        Notification::make()
                            ->title('سُجّل التصحيح')
                            ->body("أُضيفت دفعة مقابلة برقم إيصال {$reversal->receipt_number}، والدفعة الأصلية باقية في السجل.")
                            ->success()
                            ->send();
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
