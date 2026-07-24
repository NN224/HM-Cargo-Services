<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Enums\PackageStatus;
use App\Enums\ShipmentStatus;
use App\Filament\Resources\Shipments\ShipmentResource;
use App\Services\QrCode;
use App\Services\WhatsAppMessageService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * A shipment and the boxes it is made of.
 *
 * Package status is per row on purpose. A shipment sitting at
 * partial_at_destination is telling the operator that some box has not
 * arrived, and this is the only screen that says which one.
 */
class ViewShipment extends ViewRecord
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('whatsappIntake')
                ->label('واتساب: رابط التتبع')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->action(function (): void {
                    $this->record->markIntakeNotified();
                    $url = (new WhatsAppMessageService)->intakeUrl($this->record);
                    $this->js('window.open('.json_encode($url).', "_blank")');
                }),

            Action::make('whatsappArrival')
                ->label('واتساب: إشعار الوصول')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('success')
                ->visible(fn (): bool => in_array($this->record->status, [
                    ShipmentStatus::ReadyForCollection,
                    ShipmentStatus::Collected,
                ], true))
                ->action(function (): void {
                    $this->record->markArrivalNotified();
                    $url = (new WhatsAppMessageService)->arrivalUrl($this->record);
                    $this->js('window.open('.json_encode($url).', "_blank")');
                }),

            Action::make('printLabels')
                ->label('طباعة الملصقات')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('labels.shipment', $this->record))
                ->openUrlInNewTab(),

            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        $shipment = $this->record;
        $qr = app(QrCode::class);

        $intakeText = $shipment->intake_notified_at
            ? 'تم تجهيز الإشعار ('.$shipment->intake_notified_at->format('Y-m-d H:i').')'
            : 'لم يُرسل بعد';

        $isReady = $shipment->status === ShipmentStatus::ReadyForCollection;
        $arrivalText = match (true) {
            $shipment->arrival_notified_at !== null => 'تم إشعار الوصول ('.$shipment->arrival_notified_at->format('Y-m-d H:i').')',
            $isReady => '⚠️ تنبيه: لم يُرسل إشعار الوصول للمستلم بعد!',
            default => '—',
        };

        return $schema
            ->state([
                'reference' => $shipment->reference,
                'customer' => $shipment->customer->name,
                'recipient' => $shipment->recipient_name,
                'recipient_phone' => $shipment->recipient_phone,
                'destination' => $shipment->destinationWarehouse?->name ?? '—',
                'status' => $shipment->status->label(),
                'total_weight' => $this->formatWeight($shipment->total_weight_kg),
                'intake_notified' => $intakeText,
                'arrival_notified' => $arrivalText,
                'packages' => $shipment->packages->map(fn ($package): array => [
                    'barcode' => $package->barcode,
                    'qr' => $qr->svg($package->trackingUrl(), 110),
                    'weight' => $this->formatWeight($package->weight_kg),
                    'description' => $package->description ?: '—',
                    'source_barcode' => $package->source_barcode ?: '—',
                    'status' => $package->status,
                ])->all(),
            ])
            ->components([
                Section::make('بيانات الشحنة')
                    ->schema([
                        TextEntry::make('reference')->label('رقم الشحنة'),
                        TextEntry::make('customer')->label('العميل'),
                        TextEntry::make('recipient')->label('المستلم'),
                        TextEntry::make('recipient_phone')->label('هاتف المستلم'),
                        TextEntry::make('destination')->label('مستودع الوجهة'),
                        TextEntry::make('status')->label('حالة الشحنة')->badge(),
                        TextEntry::make('total_weight')->label('الوزن الإجمالي'),
                        TextEntry::make('intake_notified')
                            ->label('إشعار التتبع (الاستلام)')
                            ->badge()
                            ->color(fn (): string => $shipment->intake_notified_at ? 'success' : 'gray'),
                        TextEntry::make('arrival_notified')
                            ->label('إشعار الوصول للمستلم')
                            ->badge()
                            ->color(fn (): string => match (true) {
                                $shipment->arrival_notified_at !== null => 'success',
                                $isReady => 'warning',
                                default => 'gray',
                            }),
                    ])
                    ->columns(3),

                Section::make('الطرود')
                    ->schema([
                        RepeatableEntry::make('packages')
                            ->label('')
                            ->schema([
                                TextEntry::make('barcode')->label('الباركود')->copyable(),
                                ViewEntry::make('qr')
                                    ->label('رمز التتبّع')
                                    ->view('filament.entries.qr'),
                                TextEntry::make('weight')->label('الوزن'),
                                TextEntry::make('description')->label('الوصف'),
                                TextEntry::make('source_barcode')->label('باركود المورّد'),
                                TextEntry::make('status')
                                    ->label('حالة الطرد')
                                    ->badge()
                                    ->formatStateUsing(fn (PackageStatus $state): string => $state->label())
                                    // A RepeatableEntry item's array data is bound via
                                    // constantState(), not ->record(): a child closure's
                                    // $record then resolves to the parent ViewRecord's
                                    // Shipment (Eloquent's ArrayAccess silently returns null
                                    // for a missing key instead of erroring, so this compiles
                                    // and passes assertSee tests while quietly colouring every
                                    // row the same). $get('sibling_key') fails the same way
                                    // when the sibling isn't itself a registered component.
                                    // $state is the one value Filament always binds correctly
                                    // to this entry's own row, so the colour is matched on it
                                    // directly instead of a sibling field.
                                    ->color(fn (PackageStatus $state): string => match (true) {
                                        $state === PackageStatus::Cancelled => 'gray',
                                        $state->isException() => 'danger',
                                        default => 'success',
                                    }),
                            ])
                            ->columns(6),
                    ]),
            ]);
    }

    /**
     * Format a decimal(12,4) weight for display only; the stored value stays exact.
     *
     * Weight never travels as float in this project — it arrives here as the
     * numeric string produced by the model's decimal:4 cast. number_format()
     * accepts a numeric string directly, so no cast to float is needed.
     */
    private function formatWeight(string $weightKg): string
    {
        $trimmed = rtrim(rtrim(number_format($weightKg, 4, '.', ''), '0'), '.');

        return ($trimmed === '' ? '0' : $trimmed).' كغ';
    }
}
