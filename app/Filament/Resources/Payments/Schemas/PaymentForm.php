<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\Warehouse;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الدفعة')
                    ->components([
                        Select::make('customer_id')
                            ->label('العميل')
                            ->options(Customer::query()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live(),

                        Select::make('shipment_id')
                            ->label('تخصيص لشحنة محددة')
                            ->options(fn (Get $get) => Shipment::query()
                                ->where('customer_id', $get('customer_id'))
                                ->whereColumn('final_charge_cents', '>', 'paid_amount_cents')
                                ->whereNotNull('final_charge_cents')
                                ->pluck('reference', 'id')
                            )
                            ->searchable()
                            ->nullable()
                            ->disabled(fn (Get $get) => ! $get('customer_id'))
                            ->helperText('اتركه فارغاً للتخصيص التلقائي من الأقدم للأحدث.'),

                        // Shown only to a user without a warehouse of their
                        // own — an administrator. An employee's warehouse is
                        // taken from their assignment and is not theirs to
                        // choose.
                        Select::make('warehouse_id')
                            ->label('مستودع التحصيل')
                            ->options(Warehouse::query()->pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn (): bool => auth()->user()?->warehouse_id === null)
                            ->required(fn (): bool => auth()->user()?->warehouse_id === null),

                        TextInput::make('amount')
                            ->label('المبلغ (دولار)')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01),

                        Select::make('method')
                            ->label('طريقة الدفع')
                            ->options([
                                Payment::METHOD_CASH => 'نقد',
                                Payment::METHOD_WHISH => 'Whish',
                                Payment::METHOD_BANK => 'حوالة بنكية',
                                Payment::METHOD_OTHER => 'أخرى',
                            ])
                            ->required()
                            ->live(),

                        TextInput::make('custom_method_name')
                            ->label('اسم طريقة الدفع')
                            ->required(fn (Get $get) => $get('method') === Payment::METHOD_OTHER)
                            ->visible(fn (Get $get) => $get('method') === Payment::METHOD_OTHER),

                        DateTimePicker::make('collected_at')
                            ->label('تاريخ ووقت التحصيل')
                            ->required()
                            ->default(now()),

                        TextInput::make('reference')
                            ->label('مرجع اختياري')
                            ->nullable()
                            ->maxLength(255),

                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->nullable()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
