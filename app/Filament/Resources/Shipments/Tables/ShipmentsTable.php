<?php

namespace App\Filament\Resources\Shipments\Tables;

use App\Enums\Capability;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Services\WhatsAppMessageService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShipmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('رقم الشحنة')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('recipient_name')
                    ->label('المستلم')
                    ->searchable(),

                TextColumn::make('packages_count')
                    ->label('الطرود')
                    ->counts('packages')
                    ->alignCenter(),

                TextColumn::make('total_weight_kg')
                    ->label('الوزن')
                    ->formatStateUsing(fn ($state): string => rtrim(rtrim(number_format((float) $state, 4), '0'), '.').' كغ')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (ShipmentStatus $state): string => $state->label())
                    ->color(fn (ShipmentStatus $state): string => match ($state) {
                        ShipmentStatus::Draft, ShipmentStatus::AwaitingBatch, ShipmentStatus::Assigned => 'gray',
                        ShipmentStatus::Pending => 'warning',
                        ShipmentStatus::InTransit, ShipmentStatus::PartialAtTransit, ShipmentStatus::AtTransit => 'info',
                        ShipmentStatus::PartialAtDestination => 'info',
                        ShipmentStatus::ReadyForCollection, ShipmentStatus::Arrived => 'primary',
                        ShipmentStatus::Collected => 'success',
                        ShipmentStatus::Cancelled => 'danger',
                        ShipmentStatus::Exception => 'danger',
                    }),

                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->date('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(ShipmentStatus::options()),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),

                EditAction::make(),

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

                // The tracking-link handoff (which carries the secret token)
                // lives on the labels page, reached right after intake — not
                // here: the staff list deliberately never prints the token
                // (see ShipmentUiTest). The existing "رابط التتبع" copy action
                // still lets staff share it without rendering it.
                //
                // Once it has arrived the tracking link is moot; the recipient
                // needs the amount due and the invitation to collect. This
                // arrival message carries no token, so it is safe in the list.
                Action::make('whatsappArrival')
                    ->label('واتساب: إشعار الوصول')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->visible(fn (Shipment $record): bool => $record->status === ShipmentStatus::ReadyForCollection)
                    ->url(fn (Shipment $record): string => (new WhatsAppMessageService)->arrivalUrl($record))
                    ->openUrlInNewTab(),

                // A shipment leaves only while nothing depends on it: once it
                // is in a batch, its weight and revenue are already counted
                // there, so deleteSafely() refuses and names the batch.
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
            ->toolbarActions([]);
    }
}
