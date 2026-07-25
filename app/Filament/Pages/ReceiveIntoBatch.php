<?php

namespace App\Filament\Pages;

use App\Enums\BatchStatus;
use App\Enums\Capability;
use App\Filament\Resources\Batches\BatchResource;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\BatchIntakeService;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
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
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Receiving cargo into a batch that is already open.
 *
 * The money fields exist only for a user who may price. An employee
 * receiving boxes records a customer and some weights and never sees a
 *
 * @property \Filament\Schemas\Schema $form
 */
class ReceiveIntoBatch extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationLabel = 'استلام بضاعة';

    protected static string|UnitEnum|null $navigationGroup = 'العمليات';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'استلام بضاعة في رحلة';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill(['recipient_is_customer' => true]);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        // AGENTS.md: a warehouse employee operates only their assigned
        // warehouse. Same gate as ScanPackages — an administrator always
        // qualifies, otherwise a warehouse assignment is required, so a
        // user with none cannot open this page at all.
        return $user instanceof User
            && ($user->isAdministrator() || $user->warehouse_id !== null);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الاستلام')
                    ->description('استلام مباشر في رحلة مفتوحة — يُسعَّر فوراً بسعر العميل المتفق عليه.')
                    ->schema([
                        Select::make('batch_id')
                            ->label('الرحلة')
                            ->options(fn (): array => Batch::query()
                                ->where('status', BatchStatus::Open)
                                // Same route scope BatchResource applies to
                                // its own index (getEloquentQuery()) and to
                                // BatchForm's route select — reused here
                                // rather than a parallel rule, so a warehouse
                                // employee is offered only batches whose route
                                // touches their warehouse.
                                ->whereHas('route', fn (Builder $route): Builder => BatchResource::scopeRouteQuery($route))
                                ->orderByDesc('created_at')
                                ->pluck('reference', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            // The rate depends on this batch's route, so a
                            // change here must refresh it same as customer_id.
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => $set('rate_per_kg', self::agreedRatePerKg($get)))
                            ->createOptionForm([
                                TextInput::make('reference')
                                    ->label('رقم/اسم الرحلة (اختياري)')
                                    ->placeholder('اتركه فارغاً للتوليد التلقائي')
                                    ->maxLength(255)
                                    ->unique('batches', 'reference'),

                                Select::make('route_id')
                                    ->label('المسار')
                                    ->relationship(
                                        'route',
                                        'name',
                                        modifyQueryUsing: fn (Builder $query): Builder => BatchResource::scopeRouteQuery($query),
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('اسم المسار')
                                            ->required()
                                            ->maxLength(255)
                                            ->extraInputAttributes(['dir' => 'auto'])
                                            ->unique('routes', 'name'),

                                        Select::make('origin_warehouse_id')
                                            ->label('مستودع المنشأ')
                                            ->options(fn () => Warehouse::pluck('name', 'id')->all())
                                            ->required(),

                                        Select::make('destination_warehouse_id')
                                            ->label('مستودع الوجهة')
                                            ->options(fn () => Warehouse::pluck('name', 'id')->all())
                                            ->required()
                                            ->different('origin_warehouse_id'),

                                        Select::make('transit_warehouse_id')
                                            ->label('مستودع العبور (اختياري)')
                                            ->options(fn () => Warehouse::pluck('name', 'id')->all())
                                            ->different('origin_warehouse_id')
                                            ->different('destination_warehouse_id'),
                                    ])
                                    ->createOptionUsing(fn (array $data): int => Route::create($data)->id),
                            ])
                            ->createOptionUsing(fn (array $data): int => Batch::create([
                                'route_id' => $data['route_id'],
                                'reference' => filled($data['reference'] ?? null) ? $data['reference'] : null,
                            ])->id)
                            ->createOptionAction(fn (Action $action): Action => $action
                                ->authorize(fn (): bool => auth()->user()?->hasCapability(Capability::PriceShipments) ?? false)),

                        Select::make('customer_id')
                            ->label('العميل')
                            ->options(fn (): array => Customer::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => $set('rate_per_kg', self::agreedRatePerKg($get)))
                            // A walk-in customer should not send the operator to
                            // another screen mid-intake. The gate lives on the
                            // action itself via ->authorize(), the same pattern
                            // used for delete actions elsewhere (e.g.
                            // CustomersTable) — Filament re-evaluates it on every
                            // mount and call, so the option is truly absent for
                            // an unauthorized user, not merely hidden by a form
                            // schema that returns null (a crafted request could
                            // still reach the handler behind that).
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('اسم العميل')
                                    ->required(),
                                TextInput::make('phone')
                                    ->label('رقم الهاتف')
                                    ->required(),
                            ])
                            ->createOptionUsing(fn (array $data): int => Customer::create([
                                'name' => $data['name'],
                                'phone' => $data['phone'],
                            ])->id)
                            ->createOptionAction(fn (Action $action): Action => $action
                                ->authorize(fn (): bool => auth()->user()?->hasCapability(Capability::ManageCustomers) ?? false)),

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
                        // else the figure does not exist on this screen. It is
                        // read-only display only — the rate itself is applied
                        // server-side at intake (BatchIntakeService), never
                        // taken from this field, so it cannot be tampered with.
                        TextInput::make('rate_per_kg')
                            ->label('سعر الكيلو (دولار)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (): bool => auth()->user()?->hasCapability(Capability::PriceShipments) ?? false)
                            ->helperText('سعر العميل المتفق عليه على مسار هذه الرحلة.'),

                        // Shown only when this customer has no agreed rate for
                        // this route yet, and only to someone entitled to
                        // record the agreement. Everyone else is refused with
                        // a message naming who can supply it.
                        //
                        // Every other money field in this system takes dollars
                        // from the operator and converts to integer cents at
                        // the boundary (see CustomerRateForm::configure()) —
                        // this field mirrors that exactly. The key keeps its
                        // "_cents" name: dehydrateStateUsing() already turns
                        // the typed dollars into cents before the form state
                        // ever reaches BatchIntakeService, so the value behind
                        // this key is cents the same as everywhere else it is
                        // read.
                        TextInput::make('agreed_rate_per_kg_cents')
                            ->label('سعر الكيلو المتفق عليه (دولار)')
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->prefix('$')
                            ->visible(fn (Get $get): bool => $this->needsAgreedRate($get))
                            ->required(fn (Get $get): bool => $this->needsAgreedRate($get))
                            ->dehydrateStateUsing(fn (?string $state): ?int => $state === null ? null : (int) round(((float) $state) * 100))
                            ->helperText('لا يوجد سعر متفق عليه لهذا العميل على مسار هذه الرحلة.'),

                        Repeater::make('packages')
                            ->label('الطرود')
                            ->schema([
                                TextInput::make('weight_kg')
                                    ->label('الوزن (كغ)')
                                    ->numeric()
                                    ->step('0.0001')
                                    ->minValue(0.0001)
                                    ->required()
                                    ->live(onBlur: true),

                                TextInput::make('description')
                                    ->label('وصف اختياري'),

                                TextInput::make('source_barcode')
                                    ->label('باركود المورّد (اختياري)'),

                                TextInput::make('custom_rate_per_kg')
                                    ->label('سعر الكيلو الخاص بالطرد (دولار - اختياري)')
                                    ->placeholder('تلقائي (سعر المسار)')
                                    ->numeric()
                                    ->step('0.01')
                                    ->minValue(0.01)
                                    ->prefix('$')
                                    ->live(onBlur: true)
                                    ->helperText('اتركه فارغاً لاستخدام سعر المسار الافتراضي.'),
                            ])
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('إضافة طرد'),

                        Placeholder::make('estimated_summary')
                            ->label('إجمالي الاستلام الحسابي المباشر')
                            ->content(function (Get $get): string {
                                $packages = $get('packages') ?? [];
                                $totalWeight = 0;
                                $estimatedTotal = 0;

                                $defaultRateStr = self::agreedRatePerKg($get);
                                if (! $defaultRateStr && $get('agreed_rate_per_kg_cents')) {
                                    $defaultRateStr = number_format(((float) $get('agreed_rate_per_kg_cents')) / 100, 2);
                                }

                                $defaultRate = $defaultRateStr ? (float) $defaultRateStr : 0;
                                $hasCustomRate = false;

                                foreach ($packages as $pkg) {
                                    $w = (float) ($pkg['weight_kg'] ?? 0);
                                    $totalWeight += $w;

                                    $pkgRate = (isset($pkg['custom_rate_per_kg']) && filled($pkg['custom_rate_per_kg']))
                                        ? (float) $pkg['custom_rate_per_kg']
                                        : $defaultRate;

                                    if (isset($pkg['custom_rate_per_kg']) && filled($pkg['custom_rate_per_kg'])) {
                                        $hasCustomRate = true;
                                    }

                                    $estimatedTotal += round($w * $pkgRate, 2);
                                }

                                if ($totalWeight <= 0) {
                                    return 'أدخل أوزان الطرود لحساب الإجمالي المالي تلقائياً.';
                                }

                                if ($defaultRate > 0 || $hasCustomRate) {
                                    $rateInfo = $hasCustomRate ? '(يتضمن طروداً بأسعار مخصصة)' : sprintf('(بسعر $%s / كغ)', number_format($defaultRate, 2));
                                    return sprintf('⚖️ الوزن الكلي: %s كغ  |  💵 الإجمالي المقدر: $%s %s', number_format($totalWeight, 4), number_format($estimatedTotal, 2), $rateInfo);
                                }

                                return sprintf('⚖️ الوزن الكلي: %s كغ', number_format($totalWeight, 4));
                            })
                            ->visible(fn (): bool => auth()->user()?->hasCapability(Capability::PriceShipments) ?? false),
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

    /**
     * The customer's agreed rate for the selected batch's route, formatted
     * in dollars for display only — never fed back into arithmetic (D-007).
     *
     * Null whenever either select is empty or no rate exists for the pair,
     * so the field renders blank rather than a stale or misleading figure.
     */
    private static function agreedRatePerKg(Get $get): ?string
    {
        $batch = Batch::find($get('batch_id'));
        $customer = Customer::find($get('customer_id'));

        if (! $batch || ! $customer) {
            return null;
        }

        $rate = $customer->rateForRoute($batch->route);

        return $rate === null ? null : number_format($rate->ratePerKgDollars(), 2, '.', '');
    }

    /**
     * Whether the form must ask for a first agreed rate.
     *
     * Only for a user holding manage_customers. Without it the intake is
     * refused by the service, and offering a field they may not use would be
     * a worse experience than a clear message.
     */
    private function needsAgreedRate(Get $get): bool
    {
        if (! (auth()->user()?->hasCapability(Capability::ManageCustomers) ?? false)) {
            return false;
        }

        $batchId = $get('batch_id');
        $customerId = $get('customer_id');

        if (! $batchId || ! $customerId) {
            return false;
        }

        $batch = Batch::find($batchId);
        $customer = Customer::find($customerId);

        if (! $batch || ! $customer) {
            return false;
        }

        return $customer->rateForRoute($batch->route) === null;
    }

    public function receive(): void
    {
        $state = $this->form->getState();

        $batch = Batch::findOrFail($state['batch_id']);

        // canAccess() already guarantees an authenticated App\Models\User for
        // this page, so this is never null here.
        $actor = auth()->user();

        try {
            // The options list above already excludes a batch outside the
            // employee's warehouse (and Filament's own Select validation
            // would reject a tampered value against that same scoped list
            // before this line ever runs). Neither of those is the real
            // guard: BatchIntakeService checks the resolved batch's route
            // against $actor directly, so a caller that reaches the service
            // by any other path is refused there too, not just here.
            $shipment = app(BatchIntakeService::class)->receive($batch, $state, $actor);

            Notification::make()
                ->title('تم الاستلام')
                ->body("أُنشئت الشحنة {$shipment->reference} وأُسندت إلى الرحلة {$batch->reference}.")
                ->success()
                ->send();

            // Send the operator straight to the printable labels for the new
            // shipment, so the QR is printed and stuck on the box right away.
            $this->redirect(route('labels.shipment', $shipment));

            return;
        } catch (DomainException $e) {
            Notification::make()
                ->title('تعذّر الاستلام')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
