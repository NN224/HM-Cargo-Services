<?php

namespace App\Filament\Resources\Batches\Pages;

use App\Filament\Resources\Batches\BatchResource;
use App\Filament\Widgets\BatchProfitabilityWidget;
use App\Models\Batch;
use App\Services\BatchDispatchService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewBatch extends ViewRecord
{
    protected static string $resource = BatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Same rule as the edit page: a dispatched batch is locked
            // (D-016), so the button hides rather than 403ing on click.
            EditAction::make()
                ->visible(fn (Batch $record): bool => BatchResource::canEdit($record)),

            Action::make('dispatch')
                ->label('إرسال الرحلة')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn (Batch $record): bool => BatchResource::canDispatch($record))
                ->requiresConfirmation()
                ->modalHeading('تأكيد إرسال الرحلة')
                ->modalDescription('بمجرد إرسال الرحلة، سيتوقف قبول شحنات جديدة عليها.')
                ->action(function (Batch $record, BatchDispatchService $service): void {
                    $service->dispatch($record, 0);

                    Notification::make()
                        ->title('تم إرسال الرحلة.')
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
