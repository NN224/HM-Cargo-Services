<?php

namespace App\Filament\Resources\Shipments\Schemas;

use App\Enums\Capability;
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
                                    ->extraInputAttributes(['dir' => 'ltr'])
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => $set(
                                        '../../final_charge_usd',
                                        self::calculateTotal($get('../../packages')),
                                    ))
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

                                Select::make('pricing_method')
                                    ->label('طريقة التسعير')
                                    ->options([
                                        'per_kg' => 'سعر الكيلو',
                                        'fixed' => 'مقطوعية (سعر ثابت)',
                                    ])
                                    ->default('per_kg')
                                    ->required(fn (string $operation): bool => self::canEditPricing($operation))
                                    ->visible(fn (string $operation): bool => self::canEditPricing($operation))
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                        if ($state === 'fixed') {
                                            $set('custom_rate_per_kg', null);
                                        } else {
                                            $set('fixed_charge_usd', null);
                                        }

                                        $set(
                                            '../../final_charge_usd',
                                            self::calculateTotal($get('../../packages')),
                                        );
                                    }),

                                TextInput::make('custom_rate_per_kg')
                                    ->label('سعر الكيلو (دولار)')
                                    ->numeric()
                                    ->step('0.01')
                                    ->minValue(0.01)
                                    ->prefix('$')
                                    ->extraInputAttributes(['dir' => 'ltr'])
                                    ->required(fn (Get $get, string $operation): bool => self::canEditPricing($operation)
                                        && $get('pricing_method') === 'per_kg')
                                    ->visible(fn (Get $get, string $operation): bool => self::canEditPricing($operation)
                                        && $get('pricing_method') === 'per_kg')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => $set(
                                        '../../final_charge_usd',
                                        self::calculateTotal($get('../../packages')),
                                    )),

                                TextInput::make('fixed_charge_usd')
                                    ->label('السعر المقطوع (دولار)')
                                    ->numeric()
                                    ->step('0.01')
                                    ->minValue(0.01)
                                    ->prefix('$')
                                    ->extraInputAttributes(['dir' => 'ltr'])
                                    ->required(fn (Get $get, string $operation): bool => self::canEditPricing($operation)
                                        && $get('pricing_method') === 'fixed')
                                    ->visible(fn (Get $get, string $operation): bool => self::canEditPricing($operation)
                                        && $get('pricing_method') === 'fixed')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => $set(
                                        '../../final_charge_usd',
                                        self::calculateTotal($get('../../packages')),
                                    )),
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
                            })
                            ->mutateRelationshipDataBeforeFillUsing(
                                fn (array $data): array => self::packageDataForForm($data),
                            )
                            ->mutateRelationshipDataBeforeCreateUsing(
                                fn (array $data): array => self::packageDataForStorage($data),
                            )
                            ->mutateRelationshipDataBeforeSaveUsing(
                                fn (array $data): array => self::packageDataForStorage($data),
                            ),

                        TextInput::make('final_charge_usd')
                            ->label('الإجمالي النهائي المطلوب (دولار)')
                            ->numeric()
                            ->step('0.01')
                            ->minValue(0)
                            ->prefix('$')
                            ->extraInputAttributes(['dir' => 'ltr'])
                            ->required(fn (string $operation): bool => self::canEditPricing($operation))
                            ->visible(fn (string $operation): bool => self::canEditPricing($operation))
                            ->helperText('محسوب من أسعار الطرود، ويمكن تعديله يدوياً عند الحاجة.'),
                    ]),
            ]);
    }

    private static function canEditPricing(string $operation): bool
    {
        return $operation === 'edit'
            && (auth()->user()?->hasCapability(Capability::PriceShipments) ?? false);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function packageDataForForm(array $data): array
    {
        $fixedChargeCents = $data['fixed_charge_cents'] ?? null;
        $customRateCents = $data['custom_rate_per_kg_cents'] ?? null;

        $data['pricing_method'] = filled($fixedChargeCents) ? 'fixed' : 'per_kg';
        $data['custom_rate_per_kg'] = filled($customRateCents)
            ? number_format(((int) $customRateCents) / 100, 2, '.', '')
            : null;
        $data['fixed_charge_usd'] = filled($fixedChargeCents)
            ? number_format(((int) $fixedChargeCents) / 100, 2, '.', '')
            : null;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function packageDataForStorage(array $data): array
    {
        if (! (auth()->user()?->hasCapability(Capability::PriceShipments) ?? false)) {
            unset(
                $data['pricing_method'],
                $data['custom_rate_per_kg'],
                $data['fixed_charge_usd'],
            );

            return $data;
        }

        $method = $data['pricing_method'] ?? 'per_kg';
        $data['custom_rate_per_kg_cents'] = $method === 'per_kg'
            && filled($data['custom_rate_per_kg'] ?? null)
                ? (int) round(((float) $data['custom_rate_per_kg']) * 100)
                : null;
        $data['fixed_charge_cents'] = $method === 'fixed'
            && filled($data['fixed_charge_usd'] ?? null)
                ? (int) round(((float) $data['fixed_charge_usd']) * 100)
                : null;

        unset(
            $data['pricing_method'],
            $data['custom_rate_per_kg'],
            $data['fixed_charge_usd'],
        );

        return $data;
    }

    /**
     * @param  array<int|string, array<string, mixed>>|null  $packages
     */
    private static function calculateTotal(?array $packages): ?string
    {
        if (! $packages) {
            return null;
        }

        $total = 0.0;

        foreach ($packages as $package) {
            if (($package['pricing_method'] ?? 'per_kg') === 'fixed') {
                $total += round((float) ($package['fixed_charge_usd'] ?? 0), 2);
            } else {
                $weight = (float) ($package['weight_kg'] ?? 0);
                $rate = (float) ($package['custom_rate_per_kg'] ?? 0);
                $total += round($weight * $rate, 2);
            }
        }

        return $total > 0 ? number_format($total, 2, '.', '') : null;
    }
}
