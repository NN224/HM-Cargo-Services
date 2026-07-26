<?php

namespace App\Filament\Resources\Batches\Actions;

use App\Enums\PackageStatus;
use App\Models\Batch;
use App\Services\PackageJourneyService;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;

final class ManageBatchJourneyAction
{
    public static function make(): Action
    {
        return Action::make('manageBatchJourney')
            ->label('إجراء مسار جماعي للرحلة')
            ->icon('heroicon-o-map')
            ->color('primary')
            ->schema([
                Select::make('operation')
                    ->label('الإجراء الجماعي')
                    ->options(fn (): array => self::operationOptions())
                    ->required()
                    ->live()
                    ->default('advance'),

                Select::make('target_status')
                    ->label('مرحلة التصحيح الجماعي')
                    ->options(self::journeyStatusOptions())
                    ->required(fn (Get $get): bool => $get('operation') === 'correct')
                    ->visible(fn (Get $get): bool => ($get('operation') === 'correct') && (auth()->user()?->isAdministrator() ?? false)),

                Textarea::make('reason')
                    ->label(fn (Get $get): string => in_array($get('operation'), ['delay', 'correct'], true) ? 'السبب' : 'إشعار للعميل (اختياري)')
                    ->required(fn (Get $get): bool => in_array($get('operation'), ['delay', 'correct'], true)),

                Toggle::make('publish_reason')
                    ->label('إظهار السبب/الإشعار للعملاء في روابط التتبع')
                    ->default(false),
            ])
            ->action(function ($record, array $data, PackageJourneyService $service, Action $action): void {
                if (! $record instanceof Batch) {
                    $record = Batch::find($action->getLivewire()->managingBatchId ?? 0);

                    if (! $record instanceof Batch) {
                        throw new DomainException('لا يمكن تحديد الرحلة.');
                    }
                }

                $actor = auth()->user();

                try {
                    match ($data['operation']) {
                        'advance' => $service->advanceBatch(
                            $record,
                            $actor,
                            'batch_journey_progress',
                            null,
                            ($data['publish_reason'] ?? false) && filled($data['reason'] ?? '') ? (string) $data['reason'] : null,
                        ),
                        'delay' => $service->delayBatch(
                            $record,
                            $actor,
                            (string) ($data['reason'] ?? ''),
                            (bool) ($data['publish_reason'] ?? false),
                        ),
                        'correct' => $service->correctBatch(
                            $record,
                            PackageStatus::from((string) ($data['target_status'] ?? '')),
                            $actor,
                            (string) ($data['reason'] ?? ''),
                            (bool) ($data['publish_reason'] ?? false),
                        ),
                        default => throw new DomainException('إجراء رحلة غير معروف.'),
                    };

                    Notification::make()
                        ->title('تم تحديث جميع شحنات الرحلة بنجاح')
                        ->success()
                        ->send();
                } catch (DomainException $e) {
                    Notification::make()
                        ->title('تعذر تحديث الرحلة الجماعية')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /** @return array<string, string> */
    private static function journeyStatusOptions(): array
    {
        return collect(PackageStatus::journeySteps())
            ->mapWithKeys(fn (PackageStatus $status): array => [$status->value => $status->label()])
            ->all();
    }

    /** @return array<string, string> */
    private static function operationOptions(): array
    {
        $options = [
            'advance' => 'تقديم كافة طرود الرحلة للمرحلة التالية',
            'delay' => 'تسجيل تأخير جماعي للرحلة',
        ];

        if (auth()->user()?->isAdministrator() ?? false) {
            $options['correct'] = 'تصحيح مرحلة الرحلة جماعياً (خاص بالمسؤولين)';
        }

        return $options;
    }
}
