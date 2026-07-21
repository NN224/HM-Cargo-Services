<?php

namespace App\Filament\Resources\Batches\Pages;

use App\Filament\Resources\Batches\BatchResource;
use App\Filament\Widgets\BatchProfitabilityWidget;
use App\Models\Batch;
use App\Services\BatchDispatchService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewBatch extends ViewRecord
{
    protected static string $resource = BatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            Action::make('dispatch')
                ->label('إرسال الرحلة')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn (Batch $record): bool => BatchResource::canDispatch($record))
                ->schema([
                    TextInput::make('cost_per_kg_cents')
                        ->label('تكلفة الكيلو (بالسنت)')
                        ->helperText('تُحفظ هذه التكلفة على الرحلة ولا تتغير بعد الإرسال.')
                        ->integer()
                        ->minValue(0)
                        ->required(),
                ])
                ->action(function (array $data, Batch $record, BatchDispatchService $service): void {
                    $service->dispatch($record, (int) $data['cost_per_kg_cents']);

                    Notification::make()
                        ->title('تم إرسال الرحلة وتثبيت تكلفتها.')
                        ->success()
                        ->send();

                    $this->redirect(BatchResource::getUrl('view', ['record' => $record]));
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BatchProfitabilityWidget::class,
        ];
    }
}
