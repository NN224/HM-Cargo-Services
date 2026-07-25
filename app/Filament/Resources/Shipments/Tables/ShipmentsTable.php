<?php

namespace App\Filament\Resources\Shipments\Tables;

use App\Enums\Capability;
use App\Enums\ShipmentStatus;
use App\Filament\Resources\Shipments\Actions\ManageJourneyAction;
use App\Models\Shipment;
use App\Services\PackageJourneyProjection;
use App\Services\ShipmentCollectionService;
use App\Services\WhatsAppMessageService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
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
                            ->formatStateUsing(fn (ShipmentStatus $state): string => $state->label())
                            ->color(fn (ShipmentStatus $state): string => match ($state) {
                                ShipmentStatus::Draft, ShipmentStatus::AwaitingBatch, ShipmentStatus::Assigned => 'gray',
                                ShipmentStatus::Pending => 'warning',
                                ShipmentStatus::InTransit, ShipmentStatus::PartialAtTransit, ShipmentStatus::AtTransit => 'info',
                                ShipmentStatus::PartialAtDestination => 'info',
                                ShipmentStatus::ReadyForCollection, ShipmentStatus::Arrived => 'primary',
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
                SelectFilter::make('batch_id')
                    ->label('الرحلة')
                    ->relationship('batch', 'reference')
                    ->placeholder('جميع الرحلات'),

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
            ->groups([
                \Filament\Tables\Grouping\Group::make('batch.reference')
                    ->label('حسب الرحلة')
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(function (Shipment $record): string {
                        if (! $record->batch) {
                            $unassignedCount = Shipment::whereNull('batch_id')->count();
                            $unassignedWeight = rtrim(rtrim(number_format((float) Shipment::whereNull('batch_id')->sum('total_weight_kg'), 2), '0'), '.');

                            return "📦 شحنات غير مسندة لرحلة — ({$unassignedCount} شحنات | {$unassignedWeight} كغ)";
                        }

                        $batch = $record->batch;
                        $routeName = $batch->route?->name ?? '';
                        $routeSuffix = $routeName ? " ({$routeName})" : '';
                        $shipmentsCount = $batch->shipments()->count();
                        $weightSum = rtrim(rtrim(number_format((float) $batch->shipments()->sum('total_weight_kg'), 2), '0'), '.');

                        return "🚚 الرحلة: {$batch->reference}{$routeSuffix} — 📦 {$shipmentsCount} شحنات | ⚖️ {$weightSum} كغ";
                    })
                    ->collapsible(),

                \Filament\Tables\Grouping\Group::make('status')
                    ->label('حسب الحالة التشغيلية')
                    ->getTitleFromRecordUsing(fn (Shipment $record): string => 'الحالة: '.$record->status->label())
                    ->collapsible(),
            ])
            ->defaultGroup('batch.reference')
            ->groupingSettingsHidden()
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
                        ->label('رابط التتبع')
                        ->icon('heroicon-o-link')
                        ->color('gray')
                        ->action(function ($record, $livewire): void {
                            $livewire->dispatch('copy-to-clipboard', text: url('/track/'.$record->public_token));
                        })
                        ->extraAttributes(fn ($record): array => [
                            'x-on:click' => 'navigator.clipboard.writeText('
                                .json_encode(url('/track/'.$record->public_token)).')',
                        ]),

                    Action::make('printLabels')
                        ->label('طباعة الملصقات')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->url(fn (Shipment $record): string => route('labels.shipment', $record))
                        ->openUrlInNewTab(),

                    Action::make('partialCollect')
                        ->label('تسليم الطرود الواصلة')
                        ->icon('heroicon-o-check-badge')
                        ->color('warning')
                        ->visible(fn (Shipment $record): bool => in_array(
                            $record->status,
                            [
                                ShipmentStatus::PartialAtDestination,
                                ShipmentStatus::PartiallyCollected,
                                ShipmentStatus::InTransit,
                                ShipmentStatus::AtTransit,
                            ],
                            true,
                        ))
                        ->requiresConfirmation()
                        ->modalHeading('تسليم الطرود الواصلة')
                        ->modalDescription('سيتم تسليم الطرود الواصلة فقط لمستودع الوجهة وتعديل حالة الشحنة إلى (تسليم جزئي)، ويبقى متبقي الطرود قيد المتابعة.')
                        ->action(function (Shipment $record, ShipmentCollectionService $service): void {
                            $actor = auth()->user();
                            $warehouse = $actor->warehouse ?? $record->destinationWarehouse;

                            try {
                                $service->collectPartially($record, $warehouse, $actor);
                                Notification::make()
                                    ->title('تم التسليم الجزئي بنجاح')
                                    ->body('تم تسليم الطرود الواصلة وتحديث حالة الشحنة بنجاح.')
                                    ->success()
                                    ->send();
                            } catch (DomainException $e) {
                                Notification::make()
                                    ->title('تعذر التسليم الجزئي')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('whatsappArrival')
                        ->label('واتساب: إشعار الوصول')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('success')
                        ->visible(fn (Shipment $record): bool => $record->status === ShipmentStatus::ReadyForCollection)
                        ->action(function (Shipment $record, $livewire): void {
                            $record->markArrivalNotified();
                            $url = (new WhatsAppMessageService)->arrivalUrl($record);
                            $livewire->js('window.open('.json_encode($url).', "_blank")');
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
            ])
            ->toolbarActions([]);
    }
}
