<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Enums\Capability;
use App\Filament\Resources\CustomerRates\CustomerRateResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Shipments\ShipmentResource;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Services\PaymentService;
use App\Services\WhatsAppMessageService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * @property \App\Models\Customer $record
 */
class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected static ?string $title = 'تفاصيل حساب العميل';

    protected function getHeaderActions(): array
    {
        $customer = $this->record;

        return [
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
                        ->helperText(sprintf('الرصيد المتبقي المستحق على العميل حالياً: $%s', number_format($customer->outstandingCents() / 100, 2))),

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
                ->action(function (array $data, PaymentService $paymentService) use ($customer): void {
                    $actor = auth()->user();
                    $warehouseId = $actor->warehouse_id ?? Warehouse::first()?->id;

                    $paymentService->recordPayment([
                        'customer_id' => $customer->id,
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
                        ->body(sprintf('تم تسجيل دفعة بقيمة $%s للعميل %s وتخصيصها لحسابه.', number_format((float) $data['amount'], 2), $customer->name))
                        ->success()
                        ->send();

                    $this->redirect(CustomerResource::getUrl('view', ['record' => $customer]));
                }),

            Action::make('statement')
                ->label('كشف حساب / فاتورة')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(CustomerResource::getUrl('statement', ['record' => $customer])),

            Action::make('whatsapp')
                ->label('تواصل واتساب')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('gray')
                ->openUrlInNewTab()
                ->url(function () use ($customer): string {
                    $normalised = app(WhatsAppMessageService::class)->normalisePhone($customer->phone);
                    return "https://wa.me/{$normalised}?text=" . urlencode("مرحباً {$customer->name}، ننتظر تواصلك بخصوص شحناتك.");
                }),

            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        $customer = $this->record;
        $outstandingCents = $customer->outstandingCents();
        $chargedCents = (int) $customer->shipments()->whereNotNull('final_charge_cents')->sum('final_charge_cents');
        $paidCents = $customer->paidCents();

        return $schema
            ->state([
                'name' => $customer->name,
                'phone' => $customer->phone,
                'is_credit_customer' => $customer->is_credit_customer,
                'is_active' => $customer->is_active,
                'summary_outstanding' => $this->formatUsd($outstandingCents),
                'summary_charged' => $this->formatUsd($chargedCents),
                'summary_paid' => $this->formatUsd($paidCents),
                'shipments_count' => (string) $customer->shipments()->count(),
            ])
            ->components([
                Section::make('الملخص المالي والتشغيلي')
                    ->schema([
                        TextEntry::make('summary_outstanding')
                            ->label('الرصيد المتبقي المستحق')
                            ->badge()
                            ->color($outstandingCents > 0 ? 'danger' : 'success'),

                        TextEntry::make('summary_charged')
                            ->label('إجمالي المفوتر'),

                        TextEntry::make('summary_paid')
                            ->label('إجمالي المسدد'),

                        TextEntry::make('shipments_count')
                            ->label('عدد الشحنات الكلي'),
                    ])
                    ->columns(4),

                Section::make('البيانات الأساسية')
                    ->schema([
                        TextEntry::make('name')->label('اسم العميل'),
                        TextEntry::make('phone')
                            ->label('رقم الهاتف')
                            ->extraAttributes(['style' => 'direction: ltr; unicode-bidi: embed;']),
                        IconEntry::make('is_credit_customer')->label('عميل آجل')->boolean(),
                        IconEntry::make('is_active')->label('حالة الحساب (نشط)')->boolean(),
                    ])
                    ->columns(4),

                ...$this->ratesSection(),

                ...$this->recentShipmentsSection(),
            ]);
    }

    private function ratesSection(): array
    {
        $rates = $this->record->rates()->with('route')->get();

        return [
            Section::make('الأسعار المتفق عليها لكل مسار')
                ->schema([
                    RepeatableEntry::make('rates')
                        ->label('')
                        ->state($rates->map(fn (CustomerRate $rate): array => [
                            'route' => $rate->route->name,
                            'rate' => '$' . number_format($rate->rate_per_kg_cents / 100, 2),
                            'url' => CustomerRateResource::getUrl('edit', ['record' => $rate]),
                        ])->all())
                        ->schema([
                            TextEntry::make('route')->label('المسار'),
                            TextEntry::make('rate')->label('السعر لكل كغ'),
                            TextEntry::make('url')
                                ->label('')
                                ->formatStateUsing(fn (): string => 'تعديل السعر')
                                ->url(fn ($state): string => $state),
                        ])
                        ->columns(3)
                        ->visible($rates->isNotEmpty()),

                    TextEntry::make('no_rates')
                        ->label('')
                        ->state('لا توجد أسعار متفق عليها لهذا العميل بعد.')
                        ->visible($rates->isEmpty()),
                ]),
        ];
    }

    private function recentShipmentsSection(): array
    {
        $shipments = $this->record->shipments()
            ->latest('created_at')
            ->limit(10)
            ->get();

        return [
            Section::make('أحدث شحنات العميل')
                ->schema([
                    RepeatableEntry::make('recent_shipments')
                        ->label('')
                        ->state($shipments->map(fn (Shipment $s): array => [
                            'reference' => $s->reference,
                            'status' => $s->status->label(),
                            'weight' => number_format((float) $s->total_weight_kg, 2) . ' كغ',
                            'total' => $s->final_charge_cents ? '$' . number_format($s->final_charge_cents / 100, 2) : 'غير مسعر',
                            'payment_status' => $s->paymentStatusLabel(),
                            'url' => ShipmentResource::getUrl('view', ['record' => $s]),
                        ])->all())
                        ->schema([
                            TextEntry::make('reference')->label('المرجع'),
                            TextEntry::make('status')->label('حالة الشحنة'),
                            TextEntry::make('weight')->label('الوزن'),
                            TextEntry::make('total')->label('الإجمالي'),
                            TextEntry::make('payment_status')->label('حالة الدفع'),
                            TextEntry::make('url')
                                ->label('')
                                ->formatStateUsing(fn (): string => 'عرض الشحنة')
                                ->url(fn ($state): string => $state),
                        ])
                        ->columns(6)
                        ->visible($shipments->isNotEmpty()),

                    TextEntry::make('no_shipments')
                        ->label('')
                        ->state('لا توجد شحنات مسجلة لهذا العميل بعد.')
                        ->visible($shipments->isEmpty()),
                ]),
        ];
    }

    private function formatUsd(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return $sign . '$' . intdiv($absolute, 100) . '.' . str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }
}
