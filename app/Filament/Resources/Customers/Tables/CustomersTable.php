<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Enums\Capability;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Services\PaymentService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم العميل')
                    ->searchable()
                    ->sortable()
                    ->extraAttributes(['style' => 'direction: ltr; unicode-bidi: embed; text-align: right;']),

                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->extraAttributes(['style' => 'unicode-bidi: isolate; direction: ltr; text-align: right;']),

                TextColumn::make('outstanding_cents')
                    ->label('الرصيد المتبقي (ديون)')
                    ->state(fn (Customer $record): int => $record->outstandingCents())
                    ->formatStateUsing(fn ($state): string => sprintf('$%d.%02d', intdiv((int) $state, 100), (int) $state % 100))
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'danger' : 'success'),

                IconColumn::make('is_credit_customer')
                    ->label('آجل')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('debt_status')
                    ->label('الموقف المالي / الديون')
                    ->options([
                        'in_debt' => 'عملاء عليهم رصيد متبقي (ديون)',
                        'zero_balance' => 'عملاء لا ديون عليهم (رصيد صفري)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $charged = '(SELECT COALESCE(SUM(final_charge_cents), 0) FROM shipments WHERE shipments.customer_id = customers.id)';
                        $allocated = '(SELECT COALESCE(SUM(pa.amount_cents), 0) FROM payment_allocations pa INNER JOIN payments p ON p.id = pa.payment_id WHERE p.customer_id = customers.id AND p.type != \'reversal\')';

                        if (($data['value'] ?? null) === 'in_debt') {
                            return $query->whereRaw("($charged - $allocated) > 0");
                        }
                        if (($data['value'] ?? null) === 'zero_balance') {
                            return $query->whereRaw("($charged - $allocated) <= 0");
                        }

                        return $query;
                    }),
                TernaryFilter::make('is_credit_customer')->label('عميل آجل'),
                TernaryFilter::make('is_active')->label('الحالة'),
            ])
            ->defaultSort('name')
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    Action::make('recordPayment')
                        ->label('تسجيل دفعة')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->visible(fn (): bool => auth()->user()?->hasCapability(Capability::RecordPayments) ?? false)
                        ->form([
                            TextInput::make('amount')
                                ->label('مبلغ الدفعة (دولار)')
                                ->numeric()
                                ->prefix('$')
                                ->required()
                                ->minValue(0.01)
                                ->helperText(fn (Customer $record): string => sprintf('الرصيد المتبقي المستحق على العميل حالياً: $%s', number_format($record->outstandingCents() / 100, 2))),

                            Select::make('method')
                                ->label('طريقة الدفع')
                                ->options([
                                    'cash' => 'كاش (نقدي)',
                                    'whish' => 'ويش (Whish Money)',
                                    'bank_transfer' => 'تحويل بنكي',
                                    'other' => 'طريقة أخرى',
                                ])
                                ->default('cash')
                                ->required()
                                ->live(),

                            TextInput::make('custom_method_name')
                                ->label('اسم طريقة الدفع')
                                ->visible(fn (Get $get): bool => $get('method') === 'other')
                                ->required(fn (Get $get): bool => $get('method') === 'other'),

                            TextInput::make('notes')
                                ->label('ملاحظات (اختياري)'),
                        ])
                        ->action(function (array $data, Customer $record, PaymentService $paymentService): void {
                            $actor = auth()->user();
                            $warehouseId = $actor->warehouse_id ?? Warehouse::first()?->id;

                            $paymentService->recordPayment([
                                'customer_id' => $record->id,
                                'amount_cents' => (int) round(((float) $data['amount']) * 100),
                                'method' => $data['method'],
                                'custom_method_name' => $data['custom_method_name'] ?? null,
                                'collected_at' => now(),
                                'collected_by' => $actor->id,
                                'warehouse_id' => $warehouseId,
                                'notes' => $data['notes'] ?? null,
                            ]);

                            Notification::make()
                                ->title('تم تسجيل الدفعة بنجاح')
                                ->body(sprintf('تم تسجيل دفعة بقيمة $%s للعميل %s وتخصيصها لحسابه.', number_format((float) $data['amount'], 2), $record->name))
                                ->success()
                                ->send();
                        }),

                    Action::make('statement')
                        ->label('كشف حساب')
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->url(fn (Customer $record): string => CustomerResource::getUrl('statement', ['record' => $record])),

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
                    ->label('إجراءات')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->button(),
            ])
            ->toolbarActions([]);
    }
}
