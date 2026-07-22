<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Enums\PackageStatus;
use App\Filament\Resources\Shipments\ShipmentResource;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
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
            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        $shipment = $this->record;

        return $schema
            ->state([
                'reference' => $shipment->reference,
                'customer' => $shipment->customer->name,
                'recipient' => $shipment->recipient_name,
                'recipient_phone' => $shipment->recipient_phone,
                'destination' => $shipment->destinationWarehouse?->name ?? '—',
                'status' => $shipment->status->label(),
                'total_weight' => $this->formatWeight($shipment->total_weight_kg),
                'packages' => $shipment->packages->map(fn ($package): array => [
                    'barcode' => $package->barcode,
                    'weight' => $this->formatWeight($package->weight_kg),
                    'description' => $package->description ?: '—',
                    'source_barcode' => $package->source_barcode ?: '—',
                    // The raw enum, not its label: the colour closure below matches on
                    // it directly (kept as $state, not read as a sibling field — see note).
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
                    ])
                    ->columns(3),

                Section::make('الطرود')
                    ->schema([
                        RepeatableEntry::make('packages')
                            ->label('')
                            ->schema([
                                TextEntry::make('barcode')->label('الباركود')->copyable(),
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
                            ->columns(5),
                    ]),
            ]);
    }

    /**
     * Format a decimal(12,4) weight for display only; the stored value stays exact.
     */
    private function formatWeight(string|float $weightKg): string
    {
        $trimmed = rtrim(rtrim(number_format((float) $weightKg, 4, '.', ''), '0'), '.');

        return ($trimmed === '' ? '0' : $trimmed).' كغ';
    }
}
