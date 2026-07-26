<?php

namespace App\Filament\Pages;

use App\Enums\BatchStatus;
use App\Enums\PackageStatus;
use App\Enums\Capability;
use App\Enums\ShipmentStatus;
use App\Filament\Resources\Batches\BatchResource;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\BatchIntakeService;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
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
 * @property Schema $form
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
                                ->whereDoesntHave('shipments.packages', function (Builder $query) {
                                    $query->whereIn('status', [
                                        PackageStatus::InTransit,
                                        PackageStatus::ArrivedTransit,
                                        PackageStatus::DepartedTransit,
                                        PackageStatus::ArrivedDestination,
                                        PackageStatus::Collected,
                                    ]);
                                })
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
                            // A change in route no longer fetches fixed prices,
                            // but still needed for batch creation.
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::loadExistingShipmentIfAny($get, $set))
                            ->createOptionForm([
                                TextInput::make('reference')
                                    ->label('رقم/اسم الرحلة (اختياري)')
                                    ->placeholder('اتركه فارغاً للتوليد التلقائي')
                                    ->maxLength(255)
                                    ->unique('batches', 'reference'),

                                Select::make('route_id')
                                    ->label('المسار')
                                    ->options(fn (): array => BatchResource::scopeRouteQuery(Route::query())->pluck('name', 'id')->all())
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
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::loadExistingShipmentIfAny($get, $set))
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
                                    ->required()
                                    ->extraInputAttributes(['dir' => 'ltr']),
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
                            ->required(fn (Get $get): bool => ! $get('recipient_is_customer'))
                            ->extraInputAttributes(['dir' => 'ltr']),

                        // Pricing is now fully manual per package, so the fixed
                        // route rate fields (rate_per_kg, agreed_rate_per_kg_cents)
                        // have been removed.

                        Repeater::make('packages')
                            ->label('الطرود')
                            ->schema([
                                Hidden::make('id'),
                                TextInput::make('weight_kg')
                                    ->label('الوزن (كغ)')
                                    ->numeric()
                                    ->step('0.0001')
                                    ->minValue(0.0001)
                                    ->extraInputAttributes(['dir' => 'ltr'])
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $set('../../final_charge_usd', self::calculateTotal($get('../../packages')));
                                    }),

                                TextInput::make('description')
                                    ->label('وصف اختياري'),

                                TextInput::make('source_barcode')
                                    ->label('باركود المورّد (اختياري)'),

                                Select::make('pricing_method')
                                    ->label('طريقة التسعير')
                                    ->options([
                                        'per_kg' => 'سعر الكيلو',
                                        'fixed' => 'مقطوعية (سعر ثابت)',
                                    ])
                                    ->default('per_kg')
                                    ->required(fn (): bool => auth()->user()?->hasCapability(Capability::PriceShipments) ?? false)
                                    ->visible(fn (): bool => auth()->user()?->hasCapability(Capability::PriceShipments) ?? false)
                                    ->live(),

                                TextInput::make('custom_rate_per_kg')
                                    ->label('سعر الكيلو (دولار)')
                                    ->numeric()
                                    ->step('0.01')
                                    ->minValue(0.01)
                                    ->prefix('$')
                                    ->extraInputAttributes(['dir' => 'ltr'])
                                    ->required(fn (Get $get): bool => (auth()->user()?->hasCapability(Capability::PriceShipments) ?? false) && $get('pricing_method') === 'per_kg')
                                    ->visible(fn (Get $get): bool => (auth()->user()?->hasCapability(Capability::PriceShipments) ?? false) && $get('pricing_method') === 'per_kg')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $set('../../final_charge_usd', self::calculateTotal($get('../../packages')));
                                    }),

                                TextInput::make('fixed_charge_usd')
                                    ->label('السعر المقطوع (دولار)')
                                    ->numeric()
                                    ->step('0.01')
                                    ->minValue(0.01)
                                    ->prefix('$')
                                    ->extraInputAttributes(['dir' => 'ltr'])
                                    ->required(fn (Get $get): bool => (auth()->user()?->hasCapability(Capability::PriceShipments) ?? false) && $get('pricing_method') === 'fixed')
                                    ->visible(fn (Get $get): bool => (auth()->user()?->hasCapability(Capability::PriceShipments) ?? false) && $get('pricing_method') === 'fixed')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $set('../../final_charge_usd', self::calculateTotal($get('../../packages')));
                                    }),
                            ])
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('إضافة طرد'),

                        TextInput::make('final_charge_usd')
                            ->label('الإجمالي النهائي المطلوب (دولار)')
                            ->numeric()
                            ->step('0.01')
                            ->prefix('$')
                            ->extraInputAttributes(['dir' => 'ltr'])
                            ->required(fn (): bool => auth()->user()?->hasCapability(Capability::PriceShipments) ?? false)
                            ->visible(fn (): bool => auth()->user()?->hasCapability(Capability::PriceShipments) ?? false)
                            ->helperText('سيتم حفظ هذا الرقم كالمبلغ النهائي للشحنة. يمكنك تعديله يدوياً (لجبر الكسور أو للخصم).'),
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

    private static function calculateTotal(?array $packages): ?string
    {
        if (! $packages) {
            return null;
        }

        $total = 0;
        foreach ($packages as $pkg) {
            $method = $pkg['pricing_method'] ?? 'per_kg';
            if ($method === 'fixed') {
                $total += round((float) ($pkg['fixed_charge_usd'] ?? 0), 2);
            } else {
                $w = (float) ($pkg['weight_kg'] ?? 0);
                $r = (float) ($pkg['custom_rate_per_kg'] ?? 0);
                $total += round($w * $r, 2);
            }
        }

        return $total > 0 ? number_format($total, 2, '.', '') : null;
    }

    public static function loadExistingShipmentIfAny(Get $get, Set $set): void
    {
        $batchId = $get('batch_id');
        $customerId = $get('customer_id');

        if (! $batchId || ! $customerId) {
            return;
        }

        $batch = Batch::with('route')->find($batchId);
        $customer = Customer::find($customerId);

        $customerRateCents = null;
        if ($batch && $customer && $batch->route) {
            $cRate = $customer->rateForRoute($batch->route);
            if ($cRate && $cRate->rate_per_kg_cents > 0) {
                $customerRateCents = $cRate->rate_per_kg_cents;
            }
        }

        $existing = Shipment::query()
            ->where('batch_id', $batchId)
            ->where('customer_id', $customerId)
            ->whereNotIn('status', [ShipmentStatus::Collected->value, ShipmentStatus::Cancelled->value])
            ->with(['packages', 'customer'])
            ->first();

        if ($existing) {
            $previousPackageRateCents = $existing->packages
                ->pluck('custom_rate_per_kg_cents')
                ->filter(fn (?int $rateCents): bool => ($rateCents ?? 0) > 0)
                ->last();
            $defaultRateCents = $previousPackageRateCents
                ?? $existing->rate_per_kg_cents
                ?? $customerRateCents;

            $packagesState = [];
            foreach ($existing->packages as $pkg) {
                $rateCents = $pkg->fixed_charge_cents
                    ? null
                    : ($pkg->custom_rate_per_kg_cents ?? $defaultRateCents);
                $rateFormatted = $rateCents ? number_format($rateCents / 100, 2, '.', '') : null;

                $packagesState[] = [
                    'id' => $pkg->id,
                    'weight_kg' => (string) $pkg->weight_kg,
                    'description' => $pkg->description,
                    'source_barcode' => $pkg->source_barcode,
                    'pricing_method' => $pkg->fixed_charge_cents ? 'fixed' : 'per_kg',
                    'custom_rate_per_kg' => $rateFormatted,
                    'fixed_charge_usd' => $pkg->fixed_charge_cents ? number_format($pkg->fixed_charge_cents / 100, 2, '.', '') : null,
                ];
            }

            $set('packages', $packagesState);
            $set('recipient_name', $existing->recipient_name);
            $set('recipient_phone', $existing->recipient_phone);

            $isCustomer = $existing->customer && $existing->recipient_name === $existing->customer->name && $existing->recipient_phone === $existing->customer->phone;
            $set('recipient_is_customer', $isCustomer);

            $calcTotal = self::calculateTotal($packagesState);
            if ($existing->final_charge_cents) {
                $set('final_charge_usd', number_format($existing->final_charge_cents / 100, 2, '.', ''));
            } else {
                $set('final_charge_usd', $calcTotal);
            }

            Notification::make()
                ->title('تم التحرير على شحنة سابقة')
                ->body("تم العثور على الشحنة ({$existing->reference}) لهذا العميل على نفس الرحلة، وتم تحميل طرودها وأسعارها السابقة. يمكنك إضافة الطرود الجديدة إليها الآن.")
                ->info()
                ->send();
        } elseif ($customerRateCents) {
            $defaultRateFormatted = number_format($customerRateCents / 100, 2, '.', '');
            $packagesState = $get('packages') ?? [];
            if (is_array($packagesState)) {
                $updated = false;
                foreach ($packagesState as &$pkgItem) {
                    if (empty($pkgItem['custom_rate_per_kg']) && empty($pkgItem['fixed_charge_usd'])) {
                        $pkgItem['custom_rate_per_kg'] = $defaultRateFormatted;
                        $updated = true;
                    }
                }
                if ($updated) {
                    $set('packages', $packagesState);
                    $set('final_charge_usd', self::calculateTotal($packagesState));
                }
            }
        }
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
