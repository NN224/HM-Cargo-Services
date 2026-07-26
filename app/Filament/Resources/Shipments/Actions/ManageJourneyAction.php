<?php

namespace App\Filament\Resources\Shipments\Actions;

use App\Enums\PackageStatus;
use App\Models\Shipment;
use App\Services\PackageJourneyService;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;

final class ManageJourneyAction
{
    public static function make(): Action
    {
        return Action::make('manageJourney')
            ->label('تحديث طرود الشحنة')
            ->icon('heroicon-o-map')
            ->color('primary')
            ->schema([
                Select::make('operation')
                    ->label('الإجراء')
                    ->options(fn (): array => self::operationOptions())
                    ->required()
                    ->live()
                    ->default('advance'),

                CheckboxList::make('package_ids')
                    ->label('الطرود')
                    ->options(function ($record): array {
                        return $record ? $record->packages()->orderBy('id')->pluck('barcode', 'id')->all() : [];
                    })
                    ->required()
                    ->validationMessages([
                        'required' => 'اختر طرداً واحداً على الأقل.',
                    ]),

                Select::make('target_status')
                    ->label('مرحلة التصحيح')
                    ->options(self::journeyStatusOptions())
                    ->required(fn (Get $get): bool => $get('operation') === 'correct')
                    ->visible(fn (Get $get): bool => ($get('operation') === 'correct') && (auth()->user()?->isAdministrator() ?? false)),

                Textarea::make('reason')
                    ->label(fn (Get $get): string => in_array($get('operation'), ['delay', 'correct'], true) ? 'السبب' : 'إشعار للعميل (اختياري)')
                    ->required(fn (Get $get): bool => in_array($get('operation'), ['delay', 'correct'], true)),

                Toggle::make('publish_reason')
                    ->label('إظهار السبب/الإشعار للعميل في رابط التتبع')
                    ->default(false),
            ])
            ->action(function (array $data, PackageJourneyService $service, $record): void {
                $actor = auth()->user();

                try {
                    match ($data['operation']) {
                        'advance' => $service->advance(
                            $record,
                            $data['package_ids'] ?? [],
                            $actor,
                            'journey_progress',
                            null,
                            ($data['publish_reason'] ?? false) && filled($data['reason'] ?? '') ? (string) $data['reason'] : null,
                        ),
                        'delay' => $service->delay(
                            $record,
                            $data['package_ids'] ?? [],
                            $actor,
                            (string) ($data['reason'] ?? ''),
                            (bool) ($data['publish_reason'] ?? false),
                        ),
                        'correct' => $service->correct(
                            $record,
                            $data['package_ids'] ?? [],
                            PackageStatus::from((string) ($data['target_status'] ?? '')),
                            $actor,
                            (string) ($data['reason'] ?? ''),
                            (bool) ($data['publish_reason'] ?? false),
                        ),
                        default => throw new DomainException('إجراء رحلة غير معروف.'),
                    };

                    Notification::make()
                        ->title('تم تحديث رحلة الطرود')
                        ->success()
                        ->send();
                } catch (DomainException $e) {
                    Notification::make()
                        ->title('تعذر تحديث الرحلة')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /** @return array<string, string> */
    private static function operationOptions(): array
    {
        $options = [
            'advance' => 'تقديم للمرحلة التالية',
            'delay' => 'تسجيل تأخير',
        ];

        if (auth()->user()?->isAdministrator() ?? false) {
            $options['correct'] = 'تصحيح إداري';
        }

        return $options;
    }

    /** @return array<string, string> */
    private static function journeyStatusOptions(): array
    {
        return collect(PackageStatus::journeySteps())
            ->mapWithKeys(fn (PackageStatus $status): array => [$status->value => $status->label()])
            ->all();
    }
}
