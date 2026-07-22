<?php

namespace App\Filament\Resources\Shipments\Schemas;

use App\Models\Customer;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ShipmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل الشحنة')
                    // The operator has two ways to create a shipment. Naming
                    // the situation each one serves is cheaper than expecting
                    // them to infer it from which fields are present.
                    ->description('شحنة غير مرتبطة برحلة بعد — تُسعَّر عند إسنادها لاحقاً.')
                    ->schema([
                        Select::make('customer_id')
                            ->label('العميل المُحاسَب')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->helperText('العميل الذي تُحتسب عليه الفاتورة. قد يكون غير المستلم.'),

                        Select::make('destination_warehouse_id')
                            ->label('مستودع الوجهة')
                            ->relationship('destinationWarehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText('الوجهة النهائية للشحنة. إلزامي للشحنات الجديدة.'),
                    ])
                    ->columns(2),

                Section::make('المستلم')
                    ->description('الاسم والهاتف فقط. العنوان يأخذه فريق التوصيل عند التسليم.')
                    ->schema([
                        // Lifted from the client's own screen: the recipient is
                        // very often the customer, so this saves retyping.
                        Checkbox::make('recipient_is_customer')
                            ->label('المستلم هو نفسه العميل')
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(function (bool $state, Get $get, Set $set): void {
                                if (! $state) {
                                    return;
                                }
                                $customer = Customer::find($get('customer_id'));
                                if ($customer) {
                                    $set('recipient_name', $customer->name);
                                    $set('recipient_phone', $customer->phone);
                                }
                            }),

                        TextInput::make('recipient_name')
                            ->label('اسم المستلم')
                            ->required()
                            ->maxLength(255)
                            ->extraInputAttributes(['dir' => 'auto']),

                        TextInput::make('recipient_phone')
                            ->label('هاتف المستلم')
                            ->required()
                            ->maxLength(32)
                            ->extraInputAttributes(['dir' => 'ltr', 'style' => 'text-align:left'])
                            ->rule('regex:/^\+?[0-9\s\-()]{7,}$/')
                            ->validationMessages([
                                'regex' => 'رقم الهاتف يجب أن يحتوي على أرقام فقط، ويمكن أن يبدأ بـ + ويحوي مسافات أو شرطات.',
                            ]),
                    ])
                    ->columns(2),

                Section::make('الطرود')
                    ->description('كل طرد يحصل على باركود يولّده النظام تلقائياً.')
                    ->schema([
                        Repeater::make('packages')
                            ->label('')
                            ->relationship()
                            ->schema([
                                TextInput::make('weight_kg')
                                    ->label('الوزن (كغ)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.0001)
                                    ->step(0.0001)
                                    ->live(onBlur: true)
                                    ->suffix('kg')
                                    ->helperText('الوزن لا يُقرَّب.'),

                                TextInput::make('description')
                                    ->label('الوصف (اختياري)')
                                    ->maxLength(255)
                                    ->extraInputAttributes(['dir' => 'auto']),

                                TextInput::make('source_barcode')
                                    ->label('باركود المورّد (اختياري)')
                                    ->maxLength(255)
                                    ->extraInputAttributes(['dir' => 'ltr', 'style' => 'text-align:left'])
                                    ->helperText('امسح باركود المورّد إن وُجد. لا يحل محل باركود النظام.'),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->minItems(1)
                            ->addActionLabel('إضافة طرد')
                            ->reorderable(false)
                            ->live()
                            // Mirrors the running total on the client's screen,
                            // so the operator can check it against the scale
                            // before saving.
                            ->helperText(function (Get $get): string {
                                $total = collect($get('packages') ?? [])
                                    ->sum(fn ($row) => (float) ($row['weight_kg'] ?? 0));

                                return 'الوزن الإجمالي: '.number_format($total, 4).' كغ';
                            }),
                    ]),
            ]);
    }
}
