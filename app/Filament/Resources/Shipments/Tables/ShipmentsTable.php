<?php

namespace App\Filament\Resources\Shipments\Tables;

use App\Enums\Capability;
use App\Enums\ShipmentStatus;
use App\Filament\Resources\Shipments\Actions\ManageJourneyAction;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Services\PackageJourneyProjection;
use App\Services\PaymentService;
use App\Services\ShipmentCollectionService;
use App\Services\WhatsAppMessageService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShipmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    // 1. Top Card Header: Reference, Batch, Date
                    Grid::make(3)->schema([
                        TextColumn::make('reference')
                            ->description('رقم الشحنة', 'above')
                            ->weight('bold')
                            ->searchable()
                            ->sortable()
                            ->copyable(),

                        TextColumn::make('batch.reference')
                            ->description('الرحلة', 'above')
                            ->icon('heroicon-m-truck')
                            ->placeholder('غير مسندة لرحلة')
                            ->searchable()
                            ->sortable(),

                        TextColumn::make('created_at')
                            ->description('التاريخ', 'above')
                            ->date('Y-m-d')
                            ->sortable(),
                    ]),

                    // 2. Package Journey Stepper Bar positioned right at the TOP of the card!
                    ViewColumn::make('journey')
                        ->view('filament.tables.columns.package-journey')
                        ->state(fn (Shipment $record): array => app(PackageJourneyProjection::class)->forShipment($record)),

                    // 3. Customer & Recipient Information
                    Grid::make(2)->schema([
                        TextColumn::make('customer.name')
                            ->description('العميل', 'above')
                            ->icon('heroicon-m-user')
                            ->searchable()
                            ->sortable(),

                        TextColumn::make('recipient_name')
                            ->description('المستلم', 'above')
                            ->icon('heroicon-m-truck')
                            ->searchable(),
                    ]),

                    // 4. Package count & Weight metrics
                    Grid::make(2)->schema([
                        TextColumn::make('packages_count')
                            ->description('الطرود', 'above')
                            ->icon('heroicon-m-cube')
                            ->counts('packages'),

                        TextColumn::make('total_weight_kg')
                            ->description('الوزن', 'above')
                            ->icon('heroicon-m-scale')
                            ->formatStateUsing(fn ($state): string => rtrim(rtrim(number_format((float) $state, 4), '0'), '.').' كغ')
                            ->sortable(),
                    ]),

                    // 5. Operational Status & Payment Status Badges
                    Grid::make(2)->schema([
                        TextColumn::make('status')
                            ->description('الحالة', 'above')
                            ->badge()
                            ->formatStateUsing(fn (ShipmentStatus $state, Shipment $record): string => app(\App\Services\PackageJourneyProjection::class)->labelForShipment($record->batch?->route, $state))
                            ->color(fn (ShipmentStatus $state): string => match ($state) {
                                ShipmentStatus::Draft, ShipmentStatus::AwaitingBatch, ShipmentStatus::Assigned => 'gray',
                                ShipmentStatus::Pending => 'warning',
                                ShipmentStatus::InTransit, ShipmentStatus::PartialAtTransit, ShipmentStatus::AtTransit => 'info',
                                ShipmentStatus::PartialAtDestination => 'info',
                                ShipmentStatus::ReadyForCollection, ShipmentStatus::ReadyForCollection => 'primary',
                                ShipmentStatus::Collected, ShipmentStatus::PartiallyCollected => 'success',
                                ShipmentStatus::Cancelled => 'danger',
                                ShipmentStatus::Exception => 'danger',
                            }),

                        TextColumn::make('payment_status')
                            ->description('حالة الدفع', 'above')
                            ->state(fn (Shipment $record): string => $record->paymentStatusLabel())
                            ->badge()
                            ->color(fn (string $state): string => match (true) {
                                str_contains($state, 'بالكامل') => 'success',
                                str_contains($state, 'جزئياً') => 'warning',
                                str_contains($state, 'غير مدفوع') => 'danger',
                                default => 'gray',
                            }),
                    ]),

                    // 6. Notification Status Badge
                    Grid::make(1)->schema([
                        TextColumn::make('notification_status')
                            ->description('الإشعار', 'above')
                            ->state(function (Shipment $record): string {
                                if ($record->arrival_notified_at !== null) {
                                    return 'تم إشعار الوصول';
                                }
                                if ($record->status === ShipmentStatus::ReadyForCollection) {
                                    return '⚠️ يحتاج إشعار وصول!';
                                }
                                if ($record->intake_notified_at !== null) {
                                    return 'تم إرسال التتبع';
                                }

                                return '—';
                            })
                            ->badge()
                            ->color(fn (string $state): string => match (true) {
                                str_contains($state, 'تم') => 'success',
                                str_contains($state, '⚠️') => 'warning',
                                default => 'gray',
                            }),
                    ]),
                ])->space(3),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة التشغيلية')
                    ->options(ShipmentStatus::options()),

                SelectFilter::make('delivery_status')
                    ->label('موقف التسليم')
                    ->options([
                        'collected' => 'تم التسليم للمستلم',
                        'not_collected' => 'لم يتم التسليم بعد',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (($data['value'] ?? null) === 'collected') {
                            return $query->where('status', ShipmentStatus::Collected->value);
                        }
                        if (($data['value'] ?? null) === 'not_collected') {
                            return $query->where('status', '!=', ShipmentStatus::Collected->value);
                        }

                        return $query;
                    }),

                SelectFilter::make('payment_filter')
                    ->label('تصفية المالية والدفع')
                    ->options([
                        'paid' => 'مدفوع بالكامل',
                        'unpaid' => 'غير مدفوع / متبقي عليه مبلغ',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (($data['value'] ?? null) === 'paid') {
                            return $query->whereNotNull('final_charge_cents')
                                ->whereRaw('(SELECT COALESCE(SUM(pa.amount_cents), 0) FROM payment_allocations pa INNER JOIN payments p ON p.id = pa.payment_id WHERE pa.shipment_id = shipments.id AND p.type != \'reversal\') >= shipments.final_charge_cents');
                        }
                        if (($data['value'] ?? null) === 'unpaid') {
                            return $query->whereNotNull('final_charge_cents')
                                ->whereRaw('(SELECT COALESCE(SUM(pa.amount_cents), 0) FROM payment_allocations pa INNER JOIN payments p ON p.id = pa.payment_id WHERE pa.shipment_id = shipments.id AND p.type != \'reversal\') < shipments.final_charge_cents');
                        }

                        return $query;
                    }),
            ])
            ->extraAttributes([
                'class' => 'fi-transparent-panel',
                'style' => 'background-color: transparent !important; box-shadow: none !important; border: none !important; --ring-color: transparent;',
            ])
            ->defaultSort('created_at', 'desc')
            ->actionsPosition(RecordActionsPosition::BeforeColumns)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    ManageJourneyAction::make(),

                    Action::make('copyTrackingLink')
                        ->label('نسخ رابط التتبع')
                        ->icon('heroicon-o-link')
                        ->color('gray')
                        ->action(function (Shipment $record, $livewire): void {
                            $url = url('/track/'.$record->public_token);
                            $livewire->js('(function(){var t='.json_encode($url).';if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(t)}else{var e=document.createElement("textarea");e.value=t;e.style.position="fixed";e.style.left="-9999px";document.body.appendChild(e);e.select();document.execCommand("copy");e.remove()}})()');
                        }),

                    Action::make('printLabels')
                        ->label('طباعة الملصقات')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->url(fn (Shipment $record): string => route('labels.shipment', $record))
                        ->openUrlInNewTab(),

                    Action::make('partialCollect')
                        ->label(fn (Shipment $record): string => $record->status === ShipmentStatus::ReadyForCollection ? 'تسليم الشحنة للعميل' : 'تسليم الطرود الواصلة')
                        ->icon(fn (Shipment $record): string => $record->status === ShipmentStatus::ReadyForCollection ? 'heroicon-o-check-circle' : 'heroicon-o-check-badge')
                        ->color(fn (Shipment $record): string => $record->status === ShipmentStatus::ReadyForCollection ? 'success' : 'warning')
                        ->visible(fn (Shipment $record): bool => in_array(
                            $record->status,
                            [
                                ShipmentStatus::ReadyForCollection,
                                ShipmentStatus::PartialAtDestination,
                                ShipmentStatus::PartiallyCollected,
                                ShipmentStatus::InTransit,
                                ShipmentStatus::AtTransit,
                            ],
                            true,
                        ))
                        ->requiresConfirmation()
                        ->modalHeading(fn (Shipment $record): string => $record->status === ShipmentStatus::ReadyForCollection ? 'تسليم الشحنة للعميل' : 'تسليم الطرود الواصلة')
                        ->modalDescription(fn (Shipment $record): string => $record->status === ShipmentStatus::ReadyForCollection 
                            ? 'سيتم تسليم كافة طرود الشحنة للعميل وتعديل حالتها إلى (تم التسليم).' 
                            : 'سيتم تسليم الطرود الواصلة فقط لمستودع الوجهة وتعديل حالة الشحنة إلى (تسليم جزئي)، ويبقى متبقي الطرود قيد المتابعة.')
                        ->action(function (Shipment $record, ShipmentCollectionService $service): void {
                            $actor = auth()->user();
                            $warehouse = $record->destinationWarehouse;

                            try {
                                if ($record->status === ShipmentStatus::ReadyForCollection) {
                                    $service->collect($record, $warehouse, $actor);
                                    Notification::make()
                                        ->title('تم تسليم الشحنة بنجاح')
                                        ->body('تم تسليم الشحنة بالكامل للعميل.')
                                        ->success()
                                        ->send();
                                } else {
                                    $service->collectPartially($record, $warehouse, $actor);
                                    Notification::make()
                                        ->title('تم التسليم الجزئي بنجاح')
                                        ->body('تم تسليم الطرود الواصلة وتحديث حالة الشحنة بنجاح.')
                                        ->success()
                                        ->send();
                                }
                            } catch (DomainException $e) {
                                Notification::make()
                                    ->title('تعذر التسليم')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('whatsappIntake')
                        ->label('إرسال رابط عبر واتساب')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->action(function (Shipment $record, $livewire): void {
                            $record->markIntakeNotified();
                            $url = (new \App\Services\WhatsAppMessageService)->intakeUrl($record);
                            $livewire->js('window.open('.json_encode($url).', "_blank")');
                        }),

                    Action::make('whatsappArrival')
                        ->label('إرسال إشعار وصول عبر واتساب')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('success')
                        ->visible(fn (Shipment $record): bool => $record->status === ShipmentStatus::ReadyForCollection)
                        ->action(function (Shipment $record, $livewire): void {
                            $record->markArrivalNotified();
                            $url = (new WhatsAppMessageService)->arrivalUrl($record);
                            $livewire->js('window.open('.json_encode($url).', "_blank")');
                        }),

                    Action::make('recordPayment')
                        ->label('تسجيل دفعة')
                        ->icon('heroicon-o-banknotes')
                        ->color('danger')
                        ->visible(fn (Shipment $record): bool => $record->final_charge_cents !== null
                            && $record->paid_amount_cents < $record->final_charge_cents
                        )
                        ->schema([
                            TextInput::make('amount')
                                ->label('المبلغ (دولار)')
                                ->numeric()
                                ->required()
                                ->minValue(0.01)
                                ->step(0.01)
                                ->default(fn (Shipment $record) => max(0.01, ($record->final_charge_cents - $record->paid_amount_cents) / 100))
                                ->extraInputAttributes(['dir' => 'ltr']),

                            Select::make('method')
                                ->label('طريقة الدفع')
                                ->options([
                                    Payment::METHOD_CASH => 'نقد',
                                    Payment::METHOD_WHISH => 'Whish',
                                    Payment::METHOD_BANK => 'حوالة بنكية',
                                    Payment::METHOD_OTHER => 'أخرى',
                                ])
                                ->required()
                                ->live()
                                ->default(Payment::METHOD_CASH),

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
                                ->nullable(),
                        ])
                        ->action(function (Shipment $record, array $data, PaymentService $service): void {
                            try {
                                $service->recordPayment([
                                    'customer_id' => $record->customer_id,
                                    'amount_cents' => (int) round($data['amount'] * 100),
                                    'method' => $data['method'],
                                    'custom_method_name' => $data['custom_method_name'] ?? null,
                                    'collected_at' => $data['collected_at'],
                                    'collected_by' => auth()->id(),
                                    'warehouse_id' => auth()->user()->warehouse_id ?? $record->destination_warehouse_id,
                                    'reference' => $data['reference'] ?? null,
                                    'notes' => $data['notes'] ?? null,
                                ], $record);

                                Notification::make()
                                    ->title('تم تسجيل الدفعة بنجاح')
                                    ->body('تم تسجيل دفعة بقيمة $'.number_format((float) $data['amount'], 2).' للشحنة '.$record->reference)
                                    ->success()
                                    ->send();
                            } catch (DomainException $e) {
                                Notification::make()
                                    ->title('تعذر تسجيل الدفعة')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('delete')
                        ->label('حذف')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn (): bool => auth()->user()?->hasCapability(Capability::DeleteRecords) ?? false)
                        ->authorize(fn ($record): bool => auth()->user()?->hasCapability(Capability::DeleteRecords) ?? false)
                        ->requiresConfirmation()
                        ->modalHeading('حذف الشحنة')
                        ->modalDescription('سيُحذف معها طرودها. لا يمكن التراجع عن هذا الإجراء.')
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
            ]);
    }
}
