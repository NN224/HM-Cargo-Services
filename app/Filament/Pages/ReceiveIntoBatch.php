<?php

namespace App\Filament\Pages;

use App\Enums\BatchStatus;
use App\Enums\Capability;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\User;
use App\Services\BatchIntakeService;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Receiving cargo into a batch that is already open.
 *
 * The money fields exist only for a user who may price. An employee
 * receiving boxes records a customer and some weights and never sees a
 * figure — the rate was agreed before the cargo moved (D-024).
 */
class ReceiveIntoBatch extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationLabel = 'استلام بضاعة';

    protected static ?string $title = 'استلام بضاعة في رحلة';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill(['recipient_is_customer' => true]);
    }

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الاستلام')
                    ->description('اختر الرحلة والعميل ثم أدخل أوزان الطرود.')
                    ->schema([
                        Select::make('batch_id')
                            ->label('الرحلة')
                            ->options(fn (): array => Batch::query()
                                ->where('status', BatchStatus::Open)
                                ->orderByDesc('created_at')
                                ->pluck('reference', 'id')
                                ->all())
                            ->searchable()
                            ->required(),

                        Select::make('customer_id')
                            ->label('العميل')
                            ->options(fn (): array => Customer::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->live(),

                        Checkbox::make('recipient_is_customer')
                            ->label('المستلم هو العميل')
                            ->default(true)
                            ->live(),

                        TextInput::make('recipient_name')
                            ->label('اسم المستلم')
                            ->visible(fn (Get $get): bool => ! $get('recipient_is_customer'))
                            ->required(fn (Get $get): bool => ! $get('recipient_is_customer')),

                        TextInput::make('recipient_phone')
                            ->label('هاتف المستلم')
                            ->visible(fn (Get $get): bool => ! $get('recipient_is_customer'))
                            ->required(fn (Get $get): bool => ! $get('recipient_is_customer')),

                        // Only a user who may price sees a price. For everyone
                        // else the figure does not exist on this screen.
                        TextInput::make('rate_per_kg')
                            ->label('سعر الكيلو (دولار)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (): bool => auth()->user()?->hasCapability(Capability::PriceShipments) ?? false)
                            ->helperText('سعر العميل المتفق عليه على مسار هذه الرحلة.'),

                        Repeater::make('packages')
                            ->label('الطرود')
                            ->schema([
                                TextInput::make('weight_kg')
                                    ->label('الوزن (كغ)')
                                    ->numeric()
                                    ->step('0.0001')
                                    ->minValue(0.0001)
                                    ->required(),

                                TextInput::make('description')
                                    ->label('وصف اختياري'),
                            ])
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('إضافة طرد'),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('intake-form')
                ->livewireSubmitHandler('receive')
                ->footer([
                    Actions::make([
                        Action::make('receive')
                            ->label('تسجيل الاستلام')
                            ->icon(Heroicon::OutlinedInboxArrowDown)
                            ->submit('receive'),
                    ])->fullWidth(),
                ]),
        ]);
    }

    public function receive(): void
    {
        $state = $this->form->getState();

        $batch = Batch::findOrFail($state['batch_id']);

        try {
            $shipment = app(BatchIntakeService::class)->receive($batch, $state);

            Notification::make()
                ->title('تم الاستلام')
                ->body("أُنشئت الشحنة {$shipment->reference} وأُسندت إلى الرحلة {$batch->reference}.")
                ->success()
                ->send();

            $this->form->fill(['recipient_is_customer' => true]);
        } catch (DomainException $e) {
            Notification::make()
                ->title('تعذّر الاستلام')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
